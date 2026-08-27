<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

headers();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') error(405, 'Méthode non autorisée');

$b = body();
$nom    = trim($b['nom']    ?? '');
$prenom = trim($b['prenom'] ?? '');
$email  = trim($b['email']  ?? '');
$mdp    = trim($b['mot_de_passe'] ?? '');
$tel    = trim($b['telephone'] ?? '');

// Validation
if (!$nom || !$prenom || !$email || !$mdp)
    error(422, 'Nom, prénom, email et mot de passe sont obligatoires');

if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    error(422, 'Adresse email invalide');

if (strlen($mdp) < 8)
    error(422, 'Le mot de passe doit contenir au moins 8 caractères');

if (!preg_match('/[A-Z]/', $mdp) || !preg_match('/[0-9]/', $mdp))
    error(422, 'Le mot de passe doit contenir au moins une majuscule et un chiffre');

$db = Database::connect();

// Email déjà utilisé ?
$stmt = $db->prepare('SELECT id FROM utilisateurs WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) error(409, 'Cet email est déjà utilisé');

$hash = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

$stmt = $db->prepare(
    'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, role, statut)
     VALUES (?, ?, ?, ?, ?, "user", "actif")'
);
$stmt->execute([$nom, $prenom, $email, $hash, $tel ?: null]);
$userId = (int) $db->lastInsertId();

// Abonner automatiquement à la station principale
$stmt = $db->prepare('SELECT id FROM stations WHERE statut = "actif" LIMIT 1');
$stmt->execute();
$station = $stmt->fetch();
if ($station) {
    $db->prepare('INSERT INTO abonnements (utilisateur_id, station_id) VALUES (?,?)')
       ->execute([$userId, $station['id']]);
}

// Journal
$db->prepare(
    'INSERT INTO journaux_connexion (utilisateur_id, email_tente, adresse_ip, action)
     VALUES (?, ?, ?, "inscription")'
)->execute([$userId, $email, $_SERVER['REMOTE_ADDR'] ?? null]);

// Générer token JWT directement
$token = JWT::generate(['sub' => $userId, 'email' => $email, 'role' => 'user']);

created([
    'token'       => $token,
    'utilisateur' => [
        'id'     => $userId,
        'nom'    => $nom,
        'prenom' => $prenom,
        'email'  => $email,
        'role'   => 'user',
    ]
], 'Inscription réussie');
