<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

headers();
$payload = requireAuth();
$db      = Database::connect();
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id) {
        $stmt = $db->prepare('SELECT * FROM stations WHERE id = ?');
        $stmt->execute([$id]);
        $s = $stmt->fetch();
        if (!$s) error(404, 'Station non trouvée');
        ok(['station' => $s]);
    }

    // Admin : toutes les stations / User : ses stations abonnées
    if ($payload['role'] === 'admin') {
        $stmt = $db->query(
            'SELECT s.*, u.nom createur_nom,
                    (SELECT COUNT(*) FROM mesures m WHERE m.station_id = s.id) nb_mesures,
                    (SELECT horodatage FROM mesures m WHERE m.station_id = s.id ORDER BY horodatage DESC LIMIT 1) derniere_mesure
             FROM stations s LEFT JOIN utilisateurs u ON u.id = s.cree_par
             ORDER BY s.id'
        );
    } else {
        $stmt = $db->prepare(
            'SELECT s.* FROM stations s
             INNER JOIN abonnements a ON a.station_id = s.id
             WHERE a.utilisateur_id = ?'
        );
        $stmt->execute([$payload['sub']]);
    }
    ok(['stations' => $stmt->fetchAll()]);
}

// ── POST : créer une station (admin) ──
if ($method === 'POST') {
    requireAdmin();
    $b = body();
    if (empty($b['nom']) || empty($b['adresse_ip'])) error(422, 'Nom et adresse IP obligatoires');

    $db->prepare(
        'INSERT INTO stations (nom, localisation, latitude, longitude, adresse_ip, statut, date_installation, cree_par)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $b['nom'], $b['localisation'] ?? null,
        $b['latitude'] ?? null, $b['longitude'] ?? null,
        $b['adresse_ip'], $b['statut'] ?? 'actif',
        $b['date_installation'] ?? date('Y-m-d'),
        $payload['sub'],
    ]);
    created(['id' => (int)$db->lastInsertId()], 'Station créée');
}

// ── PUT : modifier une station (admin) ──
if ($method === 'PUT') {
    requireAdmin();
    $id = (int)($_GET['id'] ?? 0);
    $b  = body();
    $champs = []; $vals = [];

    foreach (['nom','localisation','latitude','longitude','adresse_ip','statut'] as $f) {
        if (isset($b[$f])) { $champs[] = "$f = ?"; $vals[] = $b[$f]; }
    }
    if (empty($champs)) error(422, 'Aucun champ à modifier');
    $vals[] = $id;
    $db->prepare('UPDATE stations SET ' . implode(', ', $champs) . ' WHERE id = ?')->execute($vals);
    ok([], 'Station mise à jour');
}

// ── DELETE : supprimer une station (admin) ──
if ($method === 'DELETE') {
    requireAdmin();
    $id = (int)($_GET['id'] ?? 0);
    $db->prepare('DELETE FROM stations WHERE id = ?')->execute([$id]);
    ok([], 'Station supprimée');
}

error(405, 'Méthode non autorisée');
