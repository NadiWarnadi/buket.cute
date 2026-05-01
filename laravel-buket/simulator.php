<?php
/**
 * PHP Mock Server (Simulator WA Gateway)
 * Menyamar menjadi Node.js Server di Port 3000
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Helper untuk kirim JSON
function jsonResponse($data, $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// 1. Endpoint: /api/status
if ($uri === '/api/status' && $method === 'GET') {
    jsonResponse([
        'status' => 'connected',
        'session' => 'mock-session-test',
        'queue' => ['pending' => 0, 'success' => 10, 'failed' => 0]
    ]);
}

// 2. Endpoint: /api/send-text
if ($uri === '/api/send-text' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['to']) || empty($input['text'])) {
        jsonResponse(['error' => 'Parameter to dan text wajib ada'], 400);
    }

    jsonResponse([
        'success' => true, 
        'message' => 'Pesan terkirim (MOCK MODE)',
        'data' => ['id' => 'MOCK_MSG_' . time()]
    ]);
}

// 3. Endpoint: /api/send-media
if ($uri === '/api/send-media' && $method === 'POST') {
    // Multer di Node.js menggunakan multipart/form-data
    if (empty($_POST['to']) || empty($_FILES['file'])) {
        jsonResponse(['error' => 'Parameter to dan file wajib ada'], 400);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Media terkirim (MOCK MODE)',
        'data' => [
            'filename' => $_FILES['file']['name'],
            'type' => $_POST['type'] ?? 'image'
        ]
    ]);
}

// 4. Endpoint: /health
if ($uri === '/health') {
    http_response_code(200);
    echo "OK";
    exit;
}

// Jika endpoint tidak ditemukan
jsonResponse(['error' => 'Not Found'], 404);
