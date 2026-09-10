<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

$stmt = $pdo->query("SELECT nome_fantasia, cor_primaria, cor_fundo, logo_url FROM configuracoes_app LIMIT 1");
$config = $stmt->fetch();

// Força um retorno estruturado se o banco não trouxer resultados
if (!$config) {
    $config = [
        "nome_fantasia" => "Barbearia Padrão",
        "cor_primaria" => "#093390",
        "cor_fundo" => "#F9F9F6",
        "logo_url" => ""
    ];
}

echo json_encode($config);
?>