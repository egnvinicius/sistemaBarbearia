<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents("php://input"), true);
    
    $nome = $dados['nome'] ?? '';
    // Remove qualquer formatação (parênteses, traços), deixando só os números
    $telefone = preg_replace('/\D/', '', $dados['telefone'] ?? '');
    
    $login = $dados['login'] ?? '';
    $senha = $dados['senha'] ?? '';

    if (empty($nome) || empty($telefone)) {
        http_response_code(400);
        echo json_encode(["erro" => "Nome e telefone são obrigatórios."]);
        exit;
    }

    try {
        // Se NÃO preencheu login e senha, é um Cliente Padrão
        if (empty($login) && empty($senha)) {
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone) VALUES (?, ?)");
            $stmt->execute([$nome, $telefone]);
            http_response_code(201);
            echo json_encode(["sucesso" => true, "tipo" => "cliente"]);
        } 
        // Se preencheu, é um Funcionário da Barbearia
        else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, login, telefone, senha_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $login, $telefone, $hash]);
            http_response_code(201);
            echo json_encode(["sucesso" => true, "tipo" => "admin"]);
        }
    } catch (PDOException $e) {
        // Trata duplicidade de dados (telefone ou login já existentes)
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["erro" => "Este usuário ou telefone já está cadastrado."]);
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao processar o cadastro."]);
        }
    }
}
?>