<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';

$db       = Database::connect();
$nouveau  = 'Admin@1234';
$hash     = password_hash($nouveau, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

$db->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?')
   ->execute([$hash, 'admin@smart-irrigation.com']);

echo "✅ Mot de passe admin réinitialisé.<br>";
echo "Email : admin@smart-irrigation.com<br>";
echo "Mot de passe : Admin@1234<br>";
echo "Hash généré : " . $hash . "<br>";

// Vérification immédiate
$stmt = $db->prepare('SELECT mot_de_passe FROM utilisateurs WHERE email = ?');
$stmt->execute(['admin@smart-irrigation.com']);
$row  = $stmt->fetch();
echo password_verify($nouveau, $row['mot_de_passe'])
    ? "<br>✅ Vérification OK — vous pouvez vous connecter."
    : "<br>❌ Problème de vérification.";
?>
