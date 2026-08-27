<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

headers();

// Clé secrète partagée avec Node-RED
define('NODE_RED_KEY', 'smart_irrigation_2026');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error(405, 'Méthode non autorisée');
    exit;
}

$b = body();

// Vérification clé secrète
if (($b['key'] ?? '') !== NODE_RED_KEY) {
    error(401, 'Clé invalide');
    exit;
}

$db = Database::connect();

$stmt = $db->prepare(
    'INSERT INTO mesures (station_id, temperature, humidite, humidite_sol, pression, pluie, vent, lumiere)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    (int)($b['station_id'] ?? 1),
    isset($b['temp'])     && $b['temp']     !== null ? (float)$b['temp']     : null,
    isset($b['hum'])      && $b['hum']      !== null ? (float)$b['hum']      : null,
    isset($b['sol'])      && $b['sol']      !== null ? (float)$b['sol']      : null,
    isset($b['pression']) && $b['pression'] !== null ? (float)$b['pression'] : null,
    isset($b['pluie'])    && $b['pluie']    !== null ? (float)$b['pluie']    : null,
    isset($b['vent'])     && $b['vent']     !== null ? (float)$b['vent']     : null,
    isset($b['ldr'])      && $b['ldr']      !== null ? (float)$b['ldr']      : null,
]);

created(['id' => (int)$db->lastInsertId()], 'Mesure enregistrée');
