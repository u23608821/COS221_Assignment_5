<?php
// fetchProducts.php — Secure proxy to forward requests to the Wheatley API

// Set proper headers
header('Content-Type: application/json');

try {
    // Get environment variables
    $username = getenv('WHEATLEY_USERNAME') ?? '';
    $password = getenv('WHEATLEY_PASSWORD') ?? '';

    $apiUrl = 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php';

    // Validate request body
    $requestJson = file_get_contents('php://input');
    $clientRequest = json_decode($requestJson, true);

    if (!$clientRequest || !isset($clientRequest['type'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or missing request type.'
        ]);
        exit;
    }

    // Forward the request to the actual API using Basic Auth
    $options = [
        'http' => [
            'header' => [
                "Content-Type: application/json",
                "Authorization: Basic " . base64_encode("$username:$password")
            ],
            'method'  => 'POST',
            'content' => json_encode($clientRequest),
            'timeout' => 15
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($apiUrl, false, $context);

    if ($result === false) {
        throw new Exception("Failed to contact remote API.");
    }

    echo $result;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}