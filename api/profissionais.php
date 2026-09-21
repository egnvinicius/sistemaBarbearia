<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT id, nome FROM profissionais WHERE ativo = 1 ORDER BY nome ASC");
    $profissionais = $stmt->fetchAll();
    echo json_encode($profissionais);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao buscar profissionais."]);
}
?>