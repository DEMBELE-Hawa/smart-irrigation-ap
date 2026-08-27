<?php
function respond(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function ok(array $data = [], string $message = 'Succès'): void {
    respond(200, ['success' => true, 'message' => $message, 'data' => $data]);
}

function created(array $data = [], string $message = 'Créé'): void {
    respond(201, ['success' => true, 'message' => $message, 'data' => $data]);
}

function error(int $code, string $message): void {
    respond($code, ['success' => false, 'message' => $message]);
}

function headers(): void {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
}

function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
