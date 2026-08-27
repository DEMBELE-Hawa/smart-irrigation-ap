<?php
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/database.php';

function requireAuth(): array {
    $token = JWT::fromHeader();
    if (!$token) error(401, 'Token manquant');

    // Vérifier blacklist
    $db   = Database::connect();
    $hash = hash('sha256', $token);
    $stmt = $db->prepare('SELECT id FROM tokens_blacklist WHERE token_hash = ?');
    $stmt->execute([$hash]);
    if ($stmt->fetch()) error(401, 'Token révoqué — veuillez vous reconnecter');

    $payload = JWT::verify($token);
    if (!$payload) error(401, 'Token invalide ou expiré');

    return $payload;
}

function requireAdmin(): array {
    $payload = requireAuth();
    if ($payload['role'] !== 'admin') error(403, 'Accès réservé aux administrateurs');
    return $payload;
}
