<?php
// GET /api/mesures/resume.php?date=2026-07-23&jours=7&station_id=1
// Résumé journalier des mesures (Tmax, Tmin, humidité, vent, pression), utilisé par l'application
// pour calculer l'évapotranspiration de référence ET0 (FAO Penman-Monteith). Lecture seule.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

headers();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') error(405, 'Méthode non autorisée');

$db        = Database::connect();
$stationId = (int)($_GET['station_id'] ?? 1);
$jours     = max(1, min((int)($_GET['jours'] ?? 1), 31));
$date      = $_GET['date'] ?? gmdate('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
    error(400, 'Date invalide (format attendu : AAAA-MM-JJ)');
}
$fin   = $date;
$debut = gmdate('Y-m-d', strtotime($date . ' -' . ($jours - 1) . ' days'));

// Plages de vraisemblance : les valeurs hors plage (capteur débranché, bruit) sont ignorées.
// Vent : l'anémomètre mesure de 0 à 32,4 m/s.
$sql = "SELECT DATE(horodatage) AS jour,
               COUNT(*) AS nb_mesures,
               MAX(CASE WHEN temperature BETWEEN -5 AND 60 THEN temperature END) AS tmax,
               MIN(CASE WHEN temperature BETWEEN -5 AND 60 THEN temperature END) AS tmin,
               AVG(CASE WHEN temperature BETWEEN -5 AND 60 THEN temperature END) AS tmoy,
               MAX(CASE WHEN humidite BETWEEN 0 AND 100 THEN humidite END) AS hum_max,
               MIN(CASE WHEN humidite BETWEEN 0 AND 100 THEN humidite END) AS hum_min,
               AVG(CASE WHEN humidite BETWEEN 0 AND 100 THEN humidite END) AS hum_moy,
               AVG(CASE WHEN vent BETWEEN 0 AND 32.4 THEN vent END) AS vent_moy,
               SUM(CASE WHEN vent BETWEEN 0 AND 32.4 THEN 1 ELSE 0 END) AS nb_vent_valides,
               AVG(CASE WHEN pression BETWEEN 800 AND 1100 THEN pression END) AS pression_moy
          FROM mesures
         WHERE station_id = ? AND DATE(horodatage) BETWEEN ? AND ?
         GROUP BY DATE(horodatage)
         ORDER BY jour";
$stmt = $db->prepare($sql);
$stmt->execute([$stationId, $debut, $fin]);

$num = fn($v, int $d = 2) => $v === null ? null : round((float)$v, $d);
$resume = [];
foreach ($stmt->fetchAll() as $r) {
    $resume[] = [
        'jour'            => $r['jour'],
        'nb_mesures'      => (int)$r['nb_mesures'],
        'tmax'            => $num($r['tmax']),
        'tmin'            => $num($r['tmin']),
        'tmoy'            => $num($r['tmoy']),
        'hum_max'         => $num($r['hum_max'], 1),
        'hum_min'         => $num($r['hum_min'], 1),
        'hum_moy'         => $num($r['hum_moy'], 1),
        'vent_moy'        => $num($r['vent_moy']),
        'nb_vent_valides' => (int)$r['nb_vent_valides'],
        'pression_moy'    => $num($r['pression_moy'], 1),
    ];
}

ok(['station_id' => $stationId, 'debut' => $debut, 'fin' => $fin, 'jours' => $resume]);
