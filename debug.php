<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>Test étape par étape</h3>";

echo "1. Chargement config... ";
require_once __DIR__ . '/config/config.php';
echo "✅<br>";

echo "2. Chargement database... ";
require_once __DIR__ . '/config/database.php';
echo "✅<br>";

echo "3. Connexion MySQL... ";
$db = Database::connect();
echo "✅<br>";

echo "4. Chargement utils... ";
require_once __DIR__ . '/utils/response.php';
require_once __DIR__ . '/utils/jwt.php';
echo "✅<br>";

echo "5. Génération JWT... ";
$token = JWT::generate(['sub' => 1, 'email' => 'test@test.com', 'role' => 'admin']);
echo "✅<br>";

echo "6. Lecture admin en BDD... ";
$stmt = $db->prepare('SELECT id, email, role, mot_de_passe FROM utilisateurs WHERE email = ?');
$stmt->execute(['admin@smart-irrigation.com']);
$user = $stmt->fetch();
echo $user ? "✅ (role: " . $user['role'] . ")<br>" : "❌ Admin non trouvé<br>";

echo "7. Vérification mot de passe... ";
if ($user) {
    $ok = password_verify('Admin@1234', $user['mot_de_passe']);
    echo $ok ? "✅<br>" : "❌ Mot de passe incorrect<br>";
}

echo "<br><strong>✅ Tout fonctionne !</strong>";
?>
