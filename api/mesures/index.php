<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

headers();
$payload = requireAuth();
$db      = Database::connect();
$method  = $_SERVER['REQUEST_METHOD'];

// ── POST : enregistrer une mesure (Raspberry Pi → API) ──
if ($method === 'POST') {
    $b = body();
    $stationId = (int)($b['station_id'] ?? 1);

    $stmt = $db->prepare(
        'INSERT INTO mesures (station_id, temperature, humidite, humidite_sol, pression, pluie, vent, lumiere)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $stationId,
        $b['temp']      ?? null,
        $b['hum']       ?? null,
        $b['sol']       ?? null,
        $b['pression']  ?? null,
        $b['pluie']     ?? null,
        $b['vent']      ?? null,
        $b['ldr']       ?? null,
    ]);
    created(['id' => (int) $db->lastInsertId()], 'Mesure enregistrée');
}

// ── GET : récupérer l'historique ──
if ($method === 'GET') {
    $stationId = (int)($_GET['station_id'] ?? 1);
    $limite    = min((int)($_GET['limite'] ?? 50), 500);
    $depuis    = $_GET['depuis'] ?? null; // format: 2026-06-30

    $sql = 'SELECT * FROM mesures WHERE station_id = ?';
    $params = [$stationId];

    if ($depuis) {
        $sql .= ' AND DATE(horodatage) >= ?';
        $params[] = $depuis;
    }

    $sql .= ' ORDER BY horodatage DESC LIMIT ?';
    $params[] = $limite;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $mesures = $stmt->fetchAll();

    // Dernière mesure séparément
    $stmt2 = $db->prepare(
        'SELECT * FROM mesures WHERE station_id = ? ORDER BY horodatage DESC LIMIT 1'
    );
    $stmt2->execute([$stationId]);
    $derniere = $stmt2->fetch();

    ok([
        'derniere_mesure' => $derniere ?: null,
        'historique'      => $mesures,
        'total'           => count($mesures),
    ]);
}

error(405, 'Méthode non autorisée');
