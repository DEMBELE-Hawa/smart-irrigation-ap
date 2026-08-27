<?php
echo "<h2>✅ API Smart Irrigation — Test serveur</h2>";
echo "<p><strong>PHP version :</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Serveur :</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Test connexion BDD
try {
    $pdo = new PDO('mysql:host=localhost;dbname=smart_irrigation;charset=utf8mb4', 'root', '');
    echo "<p style='color:green'>✅ Connexion MySQL OK</p>";

    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs");
    echo "<p style='color:green'>✅ Table utilisateurs : " . $stmt->fetchColumn() . " enregistrement(s)</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur MySQL : " . $e->getMessage() . "</p>";
}

if (PHP_MAJOR_VERSION < 7) {
    echo "<p style='color:red'>❌ PHP " . PHP_VERSION . " trop ancien — mettez à jour vers PHP 7.4+</p>";
} else {
    echo "<p style='color:green'>✅ Version PHP compatible</p>";
}
?>
