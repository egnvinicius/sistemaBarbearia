<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

try {
    $dashboard = [];

    // 1. Faturamento Diário e Ticket Médio
    $stmt_diario = $pdo->query("
        SELECT 
            COUNT(a.id) AS total_atendimentos,
            COALESCE(SUM(s.preco), 0) AS faturamento_total,
            COALESCE(AVG(s.preco), 0) AS ticket_medio
        FROM agendamentos a
        INNER JOIN servicos s ON a.servico_id = s.id
        WHERE a.status = 'concluido' 
          AND DATE(a.data_hora_inicio) = CURRENT_DATE
    ");
    $dashboard['hoje'] = $stmt_diario->fetch();

    // 2. Índice de Abstenção (Mês Atual)
    // O NULLIF previne o erro fatal de divisão por zero caso o mês esteja vazio
    $stmt_abstencao = $pdo->query("
        SELECT 
            ROUND(COALESCE((SUM(CASE WHEN a.status = 'ausente' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100, 0), 2) AS taxa_abstencao_percentual
        FROM agendamentos a
        WHERE DATE(a.data_hora_inicio) >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
          AND DATE(a.data_hora_inicio) <= LAST_DAY(CURRENT_DATE)
          AND a.status IN ('concluido', 'ausente')
    ");
    $dashboard['mes'] = $stmt_abstencao->fetch();

    // 3. Performance Mensal por Profissional
    $stmt_ranking = $pdo->query("
        SELECT 
            p.nome AS profissional,
            COUNT(a.id) AS cortes_realizados,
            SUM(s.preco) AS valor_gerado
        FROM agendamentos a
        INNER JOIN profissionais p ON a.profissional_id = p.id
        INNER JOIN servicos s ON a.servico_id = s.id
        WHERE a.status = 'concluido'
          AND DATE(a.data_hora_inicio) >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
        GROUP BY p.id, p.nome
        ORDER BY valor_gerado DESC
    ");
    $dashboard['ranking_profissionais'] = $stmt_ranking->fetchAll();

    echo json_encode($dashboard);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao processar métricas do dashboard: " . $e->getMessage()]);
}
?>