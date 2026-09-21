<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT id, nome, preco, duracao_minutos FROM servicos ORDER BY nome ASC");
    $servicos = $stmt->fetchAll();
    echo json_encode($servicos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao buscar serviços."]);
}
?>