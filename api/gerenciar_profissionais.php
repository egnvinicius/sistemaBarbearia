<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$dados = json_decode(file_get_contents("php://input"), true);

try {
    if ($metodo === 'POST') {
        // Criar novo profissional
        $nome = $dados['nome'] ?? '';
        if (empty($nome)) {
            http_response_code(400);
            echo json_encode(["erro" => "Nome é obrigatório."]);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO profissionais (nome, ativo) VALUES (?, 1)");
        $stmt->execute([$nome]);
        echo json_encode(["sucesso" => true, "id" => $pdo->lastInsertId()]);
        
    } elseif ($metodo === 'PUT') {
        // Inativar ou reativar (Soft Delete)
        $id = $dados['id'] ?? 0;
        $ativo = $dados['ativo'] ?? 0; // 0 para inativar, 1 para ativar
        
        $stmt = $pdo->prepare("UPDATE profissionais SET ativo = ? WHERE id = ?");
        $stmt->execute([$ativo, $id]);
        echo json_encode(["sucesso" => true]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro na operação."]);
}
?>