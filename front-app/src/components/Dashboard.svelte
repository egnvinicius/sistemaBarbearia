<script>
    import { onMount } from 'svelte';

    let metricas = null;
    let erro = '';

    onMount(async () => {
        try {
            const res = await fetch('/api/dashboard.php');
            if (res.ok) {
                metricas = await res.json();
            } else {
                erro = 'Não foi possível carregar os indicadores.';
            }
        } catch (e) {
            erro = 'Erro de comunicação com o servidor.';
        }
    });

    // Função auxiliar para padronizar a exibição financeira
    function formatarMoeda(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }
</script>

<div class="dashboard-container">
    {#if erro}
        <p class="alerta">{erro}</p>
    {:else if !metricas}
        <p class="carregando">Calculando indicadores operacionais...</p>
    {:else}
        <!-- Cards de Métricas (KPIs) -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <span class="kpi-label">Faturamento Hoje</span>
                <span class="kpi-valor">{formatarMoeda(metricas.hoje.faturamento_total)}</span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Ticket Médio</span>
                <span class="kpi-valor">{formatarMoeda(metricas.hoje.ticket_medio)}</span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Atendimentos</span>
                <span class="kpi-valor">{metricas.hoje.total_atendimentos}</span>
            </div>
            <div class="kpi-card destaque">
                <span class="kpi-label">Taxa de No-Show (Mês)</span>
                <span class="kpi-valor">{metricas.mes.taxa_abstencao_percentual}%</span>
            </div>
        </div>

        <!-- Tabela Analítica de Performance -->
        <div class="tabela-container">
            <h3>Ranking de Profissionais (Mês Atual)</h3>
            <table class="tabela-ranking">
                <thead>
                    <tr>
                        <th>Profissional</th>
                        <th class="centro">Cortes Realizados</th>
                        <th class="direita">Valor Gerado</th>
                    </tr>
                </thead>
                <tbody>
                    {#each metricas.ranking_profissionais as prof}
                        <tr>
                            <td>{prof.profissional}</td>
                            <td class="centro">{prof.cortes_realizados}</td>
                            <td class="direita">{formatarMoeda(prof.valor_gerado)}</td>
                        </tr>
                    {:else}
                        <tr>
                            <td colspan="3" class="vazio">Nenhum atendimento registrado neste mês.</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {/if}
</div>

<style>
    .dashboard-container {
        margin-bottom: 30px;
    }

    .carregando {
        font-style: italic;
        color: #666;
    }

    /* Layout dos Cards de KPI */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }

    .kpi-card {
        background: #ffffff;
        border: 1px solid #d4d4d4;
        padding: 20px;
        border-radius: 4px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .kpi-card.destaque {
        /* Chama a atenção para a métrica de risco (abstenção) */
        border-bottom: 3px solid #8b0000; 
    }

    .kpi-label {
        font-size: 0.75rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }

    .kpi-valor {
        font-size: 1.6rem;
        font-weight: bold;
        color: var(--cor-primaria, #1A1A1A);
        font-family: var(--fonte-principal, 'Playfair Display', serif);
    }

    /* Tabela de Ranking */
    .tabela-container {
        background: #ffffff;
        border: 1px solid #d4d4d4;
        padding: 20px;
        border-radius: 4px;
    }

    h3 {
        font-size: 1.1rem;
        color: #333;
        margin-top: 0;
        margin-bottom: 15px;
    }

    .tabela-ranking {
        width: 100%;
        border-collapse: collapse;
    }

    .tabela-ranking th {
        text-align: left;
        padding: 12px;
        border-bottom: 2px solid #eee;
        font-size: 0.8rem;
        color: #666;
        text-transform: uppercase;
    }

    .tabela-ranking td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        color: #1A1A1A;
        font-size: 0.95rem;
    }

    .tabela-ranking tr:last-child td {
        border-bottom: none;
    }

    .centro { text-align: center !important; }
    .direita { text-align: right !important; }

    .vazio {
        text-align: center;
        font-style: italic;
        color: #999;
    }
    
    .alerta { color: #8b0000; font-weight: bold; }
</style>