<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === 'GET') {
        // Lista usuários pendentes
        $stmt = $pdo->query("SELECT id, nome, login, telefone FROM usuarios WHERE aprovado = 0 ORDER BY id DESC");
        echo json_encode($stmt->fetchAll());
        
    } elseif ($metodo === 'PUT') {
        // Aprova o usuário
        $dados = json_decode(file_get_contents("php://input"), true);
        $id = $dados['id'] ?? 0;
        
        $stmt = $pdo->prepare("UPDATE usuarios SET aprovado = 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["sucesso" => true]);
        
    } elseif ($metodo === 'DELETE') {
        // Rejeita/Exclui o pedido
        $dados = json_decode(file_get_contents("php://input"), true);
        $id = $dados['id'] ?? 0;
        
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["sucesso" => true]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao processar a requisição."]);
}
?>