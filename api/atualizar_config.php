<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents("php://input"), true);
    
    $nome = $dados['nome_fantasia'] ?? '';
    $cor_primaria = $dados['cor_primaria'] ?? '#1A1A1A';
    $cor_fundo = $dados['cor_fundo'] ?? '#F9F9F6';
    $logo_url = $dados['logo_url'] ?? '';
    $fonte_app = $dados['fonte_app'] ?? 'Arial, sans-serif';

    try {
        $stmt = $pdo->prepare("UPDATE configuracoes_app SET nome_fantasia = ?, cor_primaria = ?, cor_fundo = ?, logo_url = ?, fonte_app = ?");
        $stmt->execute([$nome, $cor_primaria, $cor_fundo, $logo_url, $fonte_app]);
        echo json_encode(["sucesso" => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao salvar as configurações."]);
    }
}
?>