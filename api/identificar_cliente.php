<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents("php://input"), true);
    
    $telefone = preg_replace('/\D/', '', $dados['telefone'] ?? ''); // Remove formatação, deixa só números
    $nome = $dados['nome'] ?? '';

    if (empty($telefone)) {
        http_response_code(400);
        echo json_encode(["erro" => "O telefone é obrigatório."]);
        exit;
    }

    try {
        // Verifica se o cliente já existe
        $stmt = $pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE telefone = ? LIMIT 1");
        $stmt->execute([$telefone]);
        $cliente = $stmt->fetch();

        if ($cliente) {
            // Cliente já cadastrado: retorna os dados para pular a etapa de nome
            echo json_encode(["status" => "encontrado", "cliente" => $cliente]);
        } else {
            // Cliente não existe
            if (empty($nome)) {
                // Se não mandou o nome, avisa o front-end que precisa pedir
                echo json_encode(["status" => "novo_cliente"]);
            } else {
                // Se mandou o nome, cadastra e já loga
                $stmtInsert = $pdo->prepare("INSERT INTO clientes (nome, telefone) VALUES (?, ?)");
                $stmtInsert->execute([$nome, $telefone]);
                
                echo json_encode([
                    "status" => "cadastrado", 
                    "cliente" => [
                        "id" => $pdo->lastInsertId(),
                        "nome" => $nome,
                        "telefone" => $telefone
                    ]
                ]);
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao processar identificação."]);
    }
}
?>