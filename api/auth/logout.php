<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../middleware/auth.php';

headers();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') error(405, 'Méthode non autorisée');

$payload = requireAuth();
$token   = JWT::fromHeader();
$hash    = hash('sha256', $token);
$expire  = date('Y-m-d H:i:s', $payload['exp']);

$db = Database::connect();
$db->prepare('INSERT IGNORE INTO tokens_blacklist (token_hash, expire_le) VALUES (?, ?)')
   ->execute([$hash, $expire]);

$db->prepare(
    'INSERT INTO journaux_connexion (utilisateur_id, adresse_ip, action)
     VALUES (?, ?, "deconnexion")'
)->execute([$payload['sub'], $_SERVER['REMOTE_ADDR'] ?? null]);

// Nettoyer les tokens expirés
$db->exec("DELETE FROM tokens_blacklist WHERE expire_le < NOW()");

ok([], 'Déconnexion réussie');
