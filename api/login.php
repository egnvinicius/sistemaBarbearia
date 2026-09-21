<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

$dados = json_decode(file_get_contents("php://input"), true);
$login = $dados['login'] ?? '';
$senha = $dados['senha'] ?? '';

$stmt = $pdo->prepare("SELECT id, senha_hash, aprovado FROM usuarios WHERE login = ? LIMIT 1");
$stmt->execute([$login]);
$usuario = $stmt->fetch();

if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
    
    // Verifica se o administrador liberou o acesso
    if ($usuario['aprovado'] == 0) {
        http_response_code(403); // Status 403: Proibido
        echo json_encode(["erro" => "Seu cadastro foi recebido e está aguardando aprovação do administrador."]);
        exit;
    }

    echo json_encode(["sucesso" => true, "token" => base64_encode($usuario['id'] . '_auth_token')]);
} else {
    http_response_code(401); // Status 401: Não autorizado
    echo json_encode(["erro" => "Credenciais inválidas."]);
}
?>