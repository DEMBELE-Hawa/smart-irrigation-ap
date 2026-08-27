<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

headers();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') error(405, 'Méthode non autorisée');

$b     = body();
$email = trim($b['email'] ?? '');
$mdp   = trim($b['mot_de_passe'] ?? '');
$ip    = $_SERVER['REMOTE_ADDR'] ?? null;

if (!$email || !$mdp) error(422, 'Email et mot de passe obligatoires');

$db = Database::connect();

// Limite 5 tentatives / heure par IP
$stmt = $db->prepare(
    "SELECT COUNT(*) FROM journaux_connexion
     WHERE adresse_ip = ? AND action = 'echec' AND horodatage > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
);
$stmt->execute([$ip]);
if ((int)$stmt->fetchColumn() >= 5) {
    error(429, 'Trop de tentatives échouées. Réessayez dans 1 heure.');
}

$stmt = $db->prepare('SELECT * FROM utilisateurs WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
    $db->prepare(
        'INSERT INTO journaux_connexion (email_tente, adresse_ip, action, details)
         VALUES (?, ?, "echec", "Mot de passe incorrect")'
    )->execute([$email, $ip]);
    error(401, 'Email ou mot de passe incorrect');
}

if ($user['statut'] !== 'actif') {
    error(403, 'Compte ' . $user['statut'] . '. Contactez l\'administrateur.');
}

// Mettre à jour dernière connexion
$db->prepare('UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?')
   ->execute([$user['id']]);

// Journal connexion réussie
$db->prepare(
    'INSERT INTO journaux_connexion (utilisateur_id, email_tente, adresse_ip, action)
     VALUES (?, ?, ?, "connexion")'
)->execute([$user['id'], $email, $ip]);

$token = JWT::generate([
    'sub'   => $user['id'],
    'email' => $user['email'],
    'role'  => $user['role'],
]);

ok([
    'token'       => $token,
    'utilisateur' => [
        'id'                 => (int) $user['id'],
        'nom'                => $user['nom'],
        'prenom'             => $user['prenom'],
        'email'              => $user['email'],
        'role'               => $user['role'],
        'derniere_connexion' => $user['derniere_connexion'],
    ]
], 'Connexion réussie');
