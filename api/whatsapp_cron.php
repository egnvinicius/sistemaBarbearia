<?php
require_once 'db.php';

// Impede que o script sofra timeout no servidor caso a fila de mensagens seja grande
set_time_limit(0);

try {
    // A consulta foca apenas no que importa: próximas 2 horas e lembrete não enviado
    $query = "
        SELECT a.id, a.data_hora_inicio, c.nome, c.telefone, s.nome AS servico
        FROM agendamentos a
        INNER JOIN clientes c ON a.cliente_id = c.id
        INNER JOIN servicos s ON a.servico_id = s.id
        WHERE a.status = 'agendado'
          AND a.lembrete_enviado = 0
          AND a.data_hora_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
    ";
    
    $stmt = $pdo->query($query);
    $agendamentos = $stmt->fetchAll();

    $disparos = 0;

    foreach ($agendamentos as $agendamento) {
        $telefone = $agendamento['telefone'];
        $nome = explode(' ', $agendamento['nome'])[0]; // Extrai apenas o primeiro nome
        $hora = date('H:i', strtotime($agendamento['data_hora_inicio']));
        
        $mensagem = "Olá, $nome! Passando para lembrar do seu horário hoje às $hora para: {$agendamento['servico']}. Te esperamos lá!";

        // AQUI ENTRA O CÓDIGO DA API DE WHATSAPP (Ex: Z-API, Evolution API, Baileys)
        // Como cada API tem um formato de POST diferente, simulamos o envio:
        $sucesso = true; // Simula que a API do WhatsApp respondeu "OK"
        
        if ($sucesso) {
            // Atualiza a "tabela fato" gravando que o lembrete já foi despachado
            $update = $pdo->prepare("UPDATE agendamentos SET lembrete_enviado = 1 WHERE id = ?");
            $update->execute([$agendamento['id']]);
            
            echo "Lembrete processado para $nome ($telefone).<br>";
            $disparos++;
        }
    }
    
    echo "Varredura concluída. $disparos mensagem(ns) enviada(s).";

} catch (Exception $e) {
    echo "Erro na execução do Cron: " . $e->getMessage();
}
?>