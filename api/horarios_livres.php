<?php
// horarios_livres.php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

// Recebe os parâmetros da URL
$profissional_id = $_GET['profissional_id'] ?? null;
$data = $_GET['data'] ?? null; // Formato esperado: YYYY-MM-DD
$servico_id = $_GET['servico_id'] ?? null;

if (!$profissional_id || !$data || !$servico_id) {
    http_response_code(400);
    echo json_encode(["erro" => "Parâmetros profissional_id, data e servico_id são obrigatórios."]);
    exit;
}

try {
    // 1. Descobrir a duração do serviço que o cliente quer agendar
    $stmt_servico = $pdo->prepare("SELECT duracao_minutos FROM servicos WHERE id = ? AND ativo = 1");
    $stmt_servico->execute([$servico_id]);
    $servico = $stmt_servico->fetch();

    if (!$servico) {
        http_response_code(404);
        echo json_encode(["erro" => "Serviço não encontrado."]);
        exit;
    }
    $duracao_minutos = $servico['duracao_minutos'];

    // 2. Buscar todos os agendamentos do profissional NAQUELE DIA
    $stmt_agendamentos = $pdo->prepare("
        SELECT data_hora_inicio, data_hora_fim 
        FROM agendamentos 
        WHERE profissional_id = ? 
          AND DATE(data_hora_inicio) = ? 
          AND status != 'cancelado'
    ");
    $stmt_agendamentos->execute([$profissional_id, $data]);
    $agendamentos_do_dia = $stmt_agendamentos->fetchAll();

    // 3. Definir o horário de expediente (ex: 09:00 às 19:00)
    $inicio_expediente = new DateTime("$data 09:00:00");
    $fim_expediente = new DateTime("$data 19:00:00");
    
    // Vamos varrer a agenda de 15 em 15 minutos para achar as vagas
    $intervalo_varredura = new DateInterval('PT15M');
    $slot_atual = clone $inicio_expediente;
    
    $horarios_disponiveis = [];

    // 4. Lógica de Varredura e Colisão
    while ($slot_atual < $fim_expediente) {
        // Calcula onde esse possível agendamento terminaria
        $slot_fim = clone $slot_atual;
        $slot_fim->modify("+$duracao_minutos minutes");

        // Se o serviço terminar depois do horário de fechamento, encerra a busca
        if ($slot_fim > $fim_expediente) {
            break; 
        }

        $conflito = false;

        // Bate o slot atual com os agendamentos já existentes no banco
        foreach ($agendamentos_do_dia as $agendado) {
            $ag_inicio = new DateTime($agendado['data_hora_inicio']);
            $ag_fim = new DateTime($agendado['data_hora_fim']);

            // Regra de Overlap (Sobreposição)
            if ($slot_atual < $ag_fim && $slot_fim > $ag_inicio) {
                $conflito = true;
                break; // Achou conflito, aborta a verificação desse slot
            }
        }

        // Se passou por toda a agenda do dia e não teve conflito, a vaga é real!
        if (!$conflito) {
            $horarios_disponiveis[] = $slot_atual->format('H:i');
        }

        // Avança 15 minutos para testar o próximo bloco
        $slot_atual->add($intervalo_varredura);
    }

    // Retorna a lista de horários limpa para o front-end montar os botões
    echo json_encode([
        "data" => $data,
        "profissional_id" => $profissional_id,
        "horarios" => $horarios_disponiveis
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro interno: " . $e->getMessage()]);
}
?>