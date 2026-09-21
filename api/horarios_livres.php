<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

$profissional_id = $_GET['profissional_id'] ?? null;
$data = $_GET['data'] ?? null;
$servico_id = $_GET['servico_id'] ?? null;

if (!$profissional_id || !$data || !$servico_id) {
    http_response_code(400);
    echo json_encode(["erro" => "Parâmetros incompletos."]);
    exit;
}

try {
    // 1. Validação de Ausência/Falta do Profissional
    $stmtAusencia = $pdo->prepare("SELECT id FROM ausencias_profissionais WHERE profissional_id = ? AND data_ausencia = ?");
    $stmtAusencia->execute([$profissional_id, $data]);
    if ($stmtAusencia->fetch()) {
        // Profissional de folga: retorna grade vazia
        echo json_encode(["data" => $data, "horarios" => []]);
        exit;
    }

    // 2. Validação do Horário de Funcionamento (Dia da Semana)
    // No PHP, date('w') retorna: 0 (Dom), 1 (Seg) ... 6 (Sáb)
    $dia_semana = date('w', strtotime($data));
    $stmtHorario = $pdo->prepare("SELECT hora_abertura, hora_fechamento FROM horarios_funcionamento WHERE dia_semana = ? AND ativo = 1");
    $stmtHorario->execute([$dia_semana]);
    $funcionamento = $stmtHorario->fetch();

    if (!$funcionamento) {
        // Loja fechada no dia da semana escolhido
        echo json_encode(["data" => $data, "horarios" => []]);
        exit;
    }

    // 3. Consulta da Duração do Serviço
    $stmtServico = $pdo->prepare("SELECT duracao_minutos FROM servicos WHERE id = ?");
    $stmtServico->execute([$servico_id]);
    $servico = $stmtServico->fetch();
    $duracao = $servico ? (int)$servico['duracao_minutos'] : 30;

    // 4. Mapeamento de Agendamentos Existentes
    $stmtAgendamentos = $pdo->prepare("
        SELECT TIME(data_hora_inicio) as hora_inicio, TIME(data_hora_fim) as hora_fim 
        FROM agendamentos 
        WHERE profissional_id = ? 
          AND DATE(data_hora_inicio) = ? 
          AND status NOT IN ('cancelado', 'ausente')
    ");
    $stmtAgendamentos->execute([$profissional_id, $data]);
    $agendamentos = $stmtAgendamentos->fetchAll();

    // 5. Geração e Filtragem dos Slots
    $horarios_disponiveis = [];
    $inicio_loop = strtotime($data . ' ' . $funcionamento['hora_abertura']);
    $fim_expediente = strtotime($data . ' ' . $funcionamento['hora_fechamento']);
    $agora = time();
    $eh_hoje = (strtotime($data) == strtotime(date('Y-m-d')));

    while ($inicio_loop < $fim_expediente) {
        $fim_previsto = $inicio_loop + ($duracao * 60);
        
        // Bloqueia se o corte passar do horário de fechar a loja
        if ($fim_previsto > $fim_expediente) break;

        $inicio_str = date('H:i:s', $inicio_loop);
        $fim_previsto_str = date('H:i:s', $fim_previsto);
        $conflito = false;

        // Bloqueia horários que já passaram (se o cliente estiver agendando para hoje)
        if ($eh_hoje && $inicio_loop <= $agora) {
            $conflito = true;
        }

        // Verifica choque com outros agendamentos do profissional
        foreach ($agendamentos as $agendado) {
            if ($inicio_str < $agendado['hora_fim'] && $fim_previsto_str > $agendado['hora_inicio']) {
                $conflito = true;
                break;
            }
        }

        if (!$conflito) {
            $horarios_disponiveis[] = date('H:i', $inicio_loop);
        }

        // Avança de 15 em 15 minutos para montar a grade
        $inicio_loop += (15 * 60); 
    }

    echo json_encode(["data" => $data, "horarios" => $horarios_disponiveis]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha ao processar a grade de horários."]);
}
?>