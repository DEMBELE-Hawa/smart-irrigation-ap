<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

// Fichier direct
if (is_file($file)) {
    return false;
}

// Dossier avec index.php
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (is_file($index)) {
        require $index;
        return true;
    }
}

// Chemin sans extension → essaye d'ajouter /index.php
$index = rtrim($file, '/') . '/index.php';
if (is_file($index)) {
    require $index;
    return true;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Not found: ' . $uri . ' (tried: ' . $index . ')']);
