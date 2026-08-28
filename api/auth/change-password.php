<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../middleware/auth.php';

headers();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') error(405, 'Méthode non autorisée');

// L'utilisateur doit être connecté ; il ne peut changer QUE son propre mot de passe
$payload = requireAuth();

$b       = body();
$actuel  = (string) ($b['mot_de_passe_actuel']  ?? '');
$nouveau = (string) ($b['nouveau_mot_de_passe'] ?? '');

if ($actuel === '' || $nouveau === '')
    error(422, 'Mot de passe actuel et nouveau mot de passe obligatoires');

if (strlen($nouveau) < 8)
    error(422, 'Le nouveau mot de passe doit contenir au moins 8 caractères');

if (!preg_match('/[A-Z]/', $nouveau) || !preg_match('/[0-9]/', $nouveau))
    error(422, 'Le nouveau mot de passe doit contenir au moins une majuscule et un chiffre');

if ($actuel === $nouveau)
    error(422, 'Le nouveau mot de passe doit être différent de l\'ancien');

$db = Database::connect();

$stmt = $db->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
$stmt->execute([$payload['sub']]);
$row = $stmt->fetch();
if (!$row) error(404, 'Utilisateur non trouvé');

if (!password_verify($actuel, $row['mot_de_passe']))
    error(401, 'Mot de passe actuel incorrect');

$hash = password_hash($nouveau, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
$db->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')
   ->execute([$hash, $payload['sub']]);

// Journal (non bloquant si la table refuse l'insertion)
try {
    $db->prepare(
        'INSERT INTO journaux_connexion (utilisateur_id, email_tente, adresse_ip, action, details)
         VALUES (?, ?, ?, "connexion", "Changement de mot de passe")'
    )->execute([$payload['sub'], $payload['email'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null]);
} catch (Throwable $e) {
    // ignore
}

ok([], 'Mot de passe modifié avec succès');
