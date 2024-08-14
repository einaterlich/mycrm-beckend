<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow requests from a specific origin
header("Access-Control-Allow-Origin: http://localhost:3000"); // Replace with your React app URL
header("Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Credentials: true"); // Allow credentials (cookies, etc.)

require 'vendor/autoload.php';
include 'DbConnect.php';

$objDb = new DbConnect();
$conn = $objDb->connect();

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$response = [];
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cookie = $_COOKIE['refreshToken'] ?? '';
    $secretKey = '1a3LM3W966D6QTJ5BJb9opunkUcw_d09NCOIJb9QZTsrneqOICoMoeYUDcd_NfaQyR787PAH98Vhue5g938jdkiyIZyJICytKlbjNBtebaHljIR6-zf3A2h3uy6pCtUFl1UhXWnV6madujY4_3SyUViRwBUOP-UudUL4wnJnKYUGDKsiZePPzBGrF4_gxJMRwF9lIWyUCHSh-PRGfvT7s1mu4-5ByYlFvGDQraP4ZiG5bC1TAKO_CnPyd1hrpdzBzNW4SfjqGKmz7IvLAHmRD-2AMQHpTU-hN2vwoA-iQxwQhfnqjM0nnwtZ0urE6HjKl6GWQW-KLnhtfw5n_84IRQ';

    if ($cookie) {
        try {
            $decoded = JWT::decode($cookie, new Key($secretKey, 'HS256'));
            if (isset($decoded->data)) {
    
                $newJwtToken = JWT::encode([
                    'iat' => time(),
                    'nbf' => time(),
                    'exp' => time() + 3600, // Token expiration (1 hour)
                    'data' => [
                        'id' => $decoded->data->userId ?? null,
                        'first_name' => $decoded->data->first_name ?? '',
                        'role' => $decoded->data->role ?? ''
                    ]
                ], $secretKey, 'HS256');


                $response = ['jwtToken' => $newJwtToken];
            } else {
                $response = ['error' => 'Invalid token structure'];
                http_response_code(401);
            }
        } catch (Exception $e) {
            $response = ['error' => 'Invalid or expired refresh token', 'details' => $e->getMessage()];
            http_response_code(401);
        }
    } else {
        $response = ['error' => 'Refresh token not found'];
        http_response_code(401);
    }
    echo json_encode($response);
}
?>
