<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
require 'vendor/autoload.php';
include 'DbConnect.php';
$objDb = new DbConnect();
$conn = $objDb->connect();


use Firebase\JWT\JWT;

$response=[];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    $userEmail = isset($data['email']) ? $data['email'] :null;
    $password = isset($data['password']) ? $data['password'] : null;
    
    if ($password && $userEmail ){
        $userData=getUserByEmail($conn,$userEmail);
        if (isset($userData)){
            if (password_verify($password, $userData['password'])){
                $token=setJWT($userData);
                $response = ['status' => 'success', 'message' => 'Login successful','token'=>$token];
            }else{
                $response = ['status' => 'error', 'message' => 'Wrong Password'];
            }
        }
        else{
            $response = ['status' => 'error', 'message' => 'Wrong Email'];
        }
    }
    else{
        $response = ['status' => 'error', 'message' => 'Email and password are required.'];
    }
    echo json_encode($response);
}
    


function getUserByEmail($conn,$userEmail){
    $sql = "SELECT * FROM users where email=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userEmail]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    return $userData ?? [];
}
function setJWT($userData) {
    $key = '1a3LM3W966D6QTJ5BJb9opunkUcw_d09NCOIJb9QZTsrneqOICoMoeYUDcd_NfaQyR787PAH98Vhue5g938jdkiyIZyJICytKlbjNBtebaHljIR6-zf3A2h3uy6pCtUFl1UhXWnV6madujY4_3SyUViRwBUOP-UudUL4wnJnKYUGDKsiZePPzBGrF4_gxJMRwF9lIWyUCHSh-PRGfvT7s1mu4-5ByYlFvGDQraP4ZiG5bC1TAKO_CnPyd1hrpdzBzNW4SfjqGKmz7IvLAHmRD-2AMQHpTU-hN2vwoA-iQxwQhfnqjM0nnwtZ0urE6HjKl6GWQW-KLnhtfw5n_84IRQ';
    $token = JWT::encode(
        array(
            'iat'    => time(),
            'nbf'    => time(),
            'exp'    => time() + 3600,
            'data'   => array(
                'id'         => $userData['id'],
                'first_name' => $userData['first_name']
            )
        ),
        $key,
        'HS256'
    );

    setcookie("token", $token, time() + 3600, "/", "", false, true);
    return $token;
}

