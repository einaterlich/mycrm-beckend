<?php
include 'DbConnect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

$objDb = new DbConnect();
$conn = $objDb->connect();

$path = explode('/', $_SERVER['REQUEST_URI']);
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case "POST":
        saveUser($conn);
        break;
    
    case "GET":
        // if(isset($path['5'])){
        //     getUsersNoPassword($conn, $path);
        //     break;
        // }
        // else{
            getUsers($conn, $path);
            break;

        // }
       

    case "PUT":
        editUser($conn,$path);
        break;
    
    case "DELETE":
        deleteUser($conn, $path);
        break;
    
    default:
        echo json_encode(['status' => 0, 'message' => 'Invalid Request Method.Please Try Again.']);
        break;
}

// Function to handle GET requests
function getUsers($conn, $path) {
    $sql = "SELECT * FROM users";
    
    if (isset($path[3]) && is_numeric($path[3])) {
        $sql .= " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$path[3]]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($result);
}
function getUsersNoPassword($conn,$path){
    if (isset($path[3]) && is_numeric($path[3]) && isset($path[4])) {
        $sql = "SELECT id, first_name, last_name, email, phone, city, address FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$path[3]]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    echo json_encode($result ?? []);
}

function checkUserEmail($conn, $email) {
    try {
        $sql = "SELECT * FROM users WHERE LOWER(email) = LOWER(?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $oldEmail = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($oldEmail) {
            $response = ['status' => 0, 'message' => 'The chosen email is already in use.'];
            echo json_encode($response);
            // Bad Request
            exit();
        }
    } catch (PDOException $e) {
        $response = ['status' => 0, 'message' => 'Database error: ' . $e->getMessage()];
        echo json_encode($response);
        exit();
    }
}
function saveUser($conn) {
    $user = json_decode(file_get_contents('php://input'), true);
    
    try {
        checkUserEmail($conn, $user['email']);
    
        $sql = "INSERT INTO users (first_name, last_name, email, phone, address, city, password)
                VALUES (:firstname, :lastname, :email, :phone, :address, :city, :password)";
    
        $stmt = $conn->prepare($sql);
        $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);
    
        $stmt->bindParam(':firstname', $user['firstname']);
        $stmt->bindParam(':lastname', $user['lastname']);
        $stmt->bindParam(':email', $user['email']);
        $stmt->bindParam(':phone', $user['phone']);
        $stmt->bindParam(':address', $user['address']);
        $stmt->bindParam(':city', $user['city']);
        $stmt->bindParam(':password', $hashedPassword);
    
        if ($stmt->execute()) {
            $response = ['status' => 1, 'message' => 'User created successfully.'];
        } else {
            $response = ['status' => 0, 'message' => 'Failed to create user.'];
          
        }
    } catch (PDOException $e) {
        $response = ['status' => 0, 'message' => 'Database error: ' . $e->getMessage()];
        echo json_encode($response);
    }
    
    echo json_encode($response);
}


// function saveUser($conn) {
//     $user = json_decode(file_get_contents('php://input'), true);

//     $sql = "INSERT INTO users (id, first_name, last_name, email, phone, address, city,password)
//             VALUES (NULL, :firstname, :lastname, :email, :phone, :address, :city, :password)";
//     $stmt = $conn->prepare($sql);
//     $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);

//     $stmt->bindParam(':firstname', $user['firstname']);
//     $stmt->bindParam(':lastname', $user['lastname']);
//     $stmt->bindParam(':email', $user['email']);
//     $stmt->bindParam(':phone', $user['phone']);
//     $stmt->bindParam(':address', $user['address']);
//     $stmt->bindParam(':city', $user['city']);
//     $stmt->bindParam(':password', $hashedPassword);

//     if ($stmt->execute()) {
//         $response = ['status' => 1, 'message' => 'User created successfully.'];
//     } else {
//         $response = ['status' => 0, 'message' => 'Failed create user.'];
//     }
//     echo json_encode($response);
// }


// Function to handle PUT requests
function editUser($conn,$path) {
    
    $user = json_decode(file_get_contents('php://input'), true);
    $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);
    
    $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, 
        address = :address, city = :city, updated_at = :updated_at ,password= :password WHERE id = :id";
   
    $stmt = $conn->prepare($sql);
    $updated_at = date('Y-m-d H:i:s');

    $stmt->bindParam(':first_name', $user['firstname']);
    $stmt->bindParam(':last_name', $user['lastname']);
    $stmt->bindParam(':email', $user['email']);
    $stmt->bindParam(':phone', $user['phone']);
    $stmt->bindParam(':address', $user['address']);
    $stmt->bindParam(':city', $user['city']);
    $stmt->bindParam(':updated_at', $updated_at);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':id', $path[3]);
    $stmt->execute();
    $affectedRows = $stmt->rowCount();

    if ($stmt->errorCode() != '00000') {
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Error: " . $errorInfo[2]);
    }


    $response = $stmt->rowCount() ? 
                ['status' => 1, 'message' => 'User updated successfully.'] : 
                ['status' => 0, 'message' => 'Failed to update User.'];
    
    echo json_encode($response);
}

function deleteUser($conn, $path) {
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$path[3]]);
    $response = $stmt->rowCount() ? 
                ['status' => 1, 'message' => 'Record deleted successfully.'] : 
                ['status' => 0, 'message' => 'Failed to delete record.'];
    
    echo json_encode($response);
}
