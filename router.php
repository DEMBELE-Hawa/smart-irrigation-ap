<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if (is_file($file)) {
    return false;
}

if (is_dir($file) && is_file($file . '/index.php')) {
    require $file . '/index.php';
    return true;
}

http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Route not found: ' . $uri]);
