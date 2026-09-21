<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'POST') {
    $dados = json_decode(file_get_contents("php://input"), true);
    
    $prof_id = $dados['profissional_id'] ?? null;
    $data = $dados['data_ausencia'] ?? null;
    $motivo = $dados['motivo'] ?? 'Folga';

    if (!$prof_id || !$data) {
        http_response_code(400);
        echo json_encode(["erro" => "Profissional e data são obrigatórios."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO ausencias_profissionais (profissional_id, data_ausencia, motivo) VALUES (?, ?, ?)");
        $stmt->execute([$prof_id, $data, $motivo]);
        
        http_response_code(201);
        echo json_encode(["sucesso" => true]);
        
    } catch (PDOException $e) {
        // Código 23000 indica que a regra UNIQUE (profissional + data) foi violada
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["erro" => "Ausência já registrada para esta data."]);
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Falha no banco de dados."]);
        }
    }
}
?>