<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

// Politique de confidentialite (Play Store) - route explicite
if ($uri === '/privacy' || $uri === '/privacy.html' || $uri === '/privacy/') {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/privacy.html');
    return true;
}

// Fichier direct (ex: /api/auth/login.php ou /ping.php)
if (is_file($file)) {
    return false;
}

// Dossier avec index.php (ex: /api/stations/ -> /api/stations/index.php)
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (is_file($index)) {
        require $index;
        return true;
    }
}

// URL sans extension -> essaye index.php dans ce dossier
// ex: /api/auth/login -> /api/auth/login.php
if (is_file($file . '.php')) {
    require $file . '.php';
    return true;
}

// ex: /api/stations -> /api/stations/index.php
if (is_file($file . '/index.php')) {
    require $file . '/index.php';
    return true;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Not found: ' . $uri]);
