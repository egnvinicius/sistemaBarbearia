<?php
// agendar.php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

// Recebe o payload em JSON do front-end
$json = file_get_contents('php://input');
$dados = json_decode($json, true);

// Validação básica de entrada
if (!isset($dados['cliente_id'], $dados['profissional_id'], $dados['servico_id'], $dados['data_hora_inicio'])) {
    http_response_code(400);
    echo json_encode(["erro" => "Dados incompletos."]);
    exit;
}

try {
    $cliente_id = $dados['cliente_id'];
    $profissional_id = $dados['profissional_id'];
    $servico_id = $dados['servico_id'];
    $data_hora_inicio = $dados['data_hora_inicio'];

    // Passo 1: Descobrir a duração do serviço
    $stmt = $pdo->prepare("SELECT duracao_minutos FROM servicos WHERE id = ? AND ativo = 1");
    $stmt->execute([$servico_id]);
    $servico = $stmt->fetch();

    if (!$servico) {
        http_response_code(404);
        echo json_encode(["erro" => "Serviço não encontrado ou inativo."]);
        exit;
    }

    // Passo 2: Calcular a data_hora_fim usando o PHP
    $inicio_dt = new DateTime($data_hora_inicio);
    // Clona para não alterar a variável original ao adicionar os minutos
    $fim_dt = clone $inicio_dt; 
    $fim_dt->modify("+" . $servico['duracao_minutos'] . " minutes");
    
    $data_hora_fim = $fim_dt->format('Y-m-d H:i:s');

    // Passo 3: Validação de Choque de Horários (A query que desenhamos)
    $query_choque = "
        SELECT id FROM agendamentos 
        WHERE profissional_id = ? 
          AND status != 'cancelado'
          AND (? < data_hora_fim AND ? > data_hora_inicio)
    ";
    $stmt_choque = $pdo->prepare($query_choque);
    $stmt_choque->execute([$profissional_id, $data_hora_inicio, $data_hora_fim]);

    if ($stmt_choque->fetch()) {
        // Retorna status 409 (Conflict)
        http_response_code(409);
        echo json_encode(["erro" => "O profissional já possui um agendamento neste horário."]);
        exit;
    }

    // Passo 4: Se chegou aqui, o horário está livre. Gravar no banco!
    $query_insert = "
        INSERT INTO agendamentos (cliente_id, profissional_id, servico_id, data_hora_inicio, data_hora_fim) 
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmt_insert = $pdo->prepare($query_insert);
    $stmt_insert->execute([$cliente_id, $profissional_id, $servico_id, $data_hora_inicio, $data_hora_fim]);

    // Retorna sucesso com o ID do novo agendamento
    http_response_code(201); // 201 Created
    echo json_encode([
        "mensagem" => "Agendamento realizado com sucesso!",
        "agendamento_id" => $pdo->lastInsertId(),
        "fim_previsto" => $data_hora_fim
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro interno no servidor: " . $e->getMessage()]);
}
?>