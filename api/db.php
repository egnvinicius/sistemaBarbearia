<?php
// db.php
$host = '127.0.0.1';
$dbname = 'barbearia_app';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha na conexão com o banco de dados. $e"]);
    exit;
}
?>