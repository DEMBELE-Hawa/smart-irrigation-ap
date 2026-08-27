<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

headers();
$payload = requireAuth();
$db      = Database::connect();
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stationId = (int)($_GET['station_id'] ?? 1);
    $limite    = min((int)($_GET['limite'] ?? 30), 200);
    $nonLues   = isset($_GET['non_lues']);

    $sql = 'SELECT a.*, u.nom, u.prenom FROM alertes a
            LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id
            WHERE a.station_id = ?';
    $params = [$stationId];

    if ($nonLues) { $sql .= ' AND a.lue = 0'; }
    $sql .= ' ORDER BY a.horodatage DESC LIMIT ?';
    $params[] = $limite;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Compter non lues
    $stmt2 = $db->prepare('SELECT COUNT(*) FROM alertes WHERE station_id = ? AND lue = 0');
    $stmt2->execute([$stationId]);

    ok(['alertes' => $stmt->fetchAll(), 'non_lues' => (int) $stmt2->fetchColumn()]);
}

if ($method === 'POST') {
    $b = body();
    $db->prepare(
        'INSERT INTO alertes (station_id, utilisateur_id, type, message, capteur, valeur_capteur)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $b['station_id']    ?? 1,
        $payload['sub'],
        $b['type']          ?? 'alerte',
        $b['message']       ?? '',
        $b['capteur']       ?? null,
        $b['valeur_capteur'] ?? null,
    ]);
    created([], 'Alerte enregistrée');
}

// PUT /alertes?id=X : marquer comme lue
if ($method === 'PUT') {
    $id = (int)($_GET['id'] ?? 0);
    $db->prepare('UPDATE alertes SET lue = 1 WHERE id = ?')->execute([$id]);
    ok([], 'Alerte marquée comme lue');
}

error(405, 'Méthode non autorisée');
