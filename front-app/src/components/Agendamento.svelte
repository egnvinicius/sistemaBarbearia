<script>
    import { onMount } from 'svelte';
    
    // Dados simulados para o teste estrutural da UI
    let clienteId = 1;
    let profissionalId = 1;
    let servicoId = 3;
    let dataEscolhida = '2026-09-04'; 
    
    let horarios = [];
    let horarioSelecionado = null;
    let mensagemStatus = '';

    async function buscarHorarios() {
        const res = await fetch(`/api/horarios_livres.php?profissional_id=${profissionalId}&data=${dataEscolhida}&servico_id=${servicoId}`);
        const dados = await res.json();
        horarios = dados.horarios || [];
    }

    async function confirmarAgendamento() {
        mensagemStatus = 'Processando...';
        
        const payload = {
            cliente_id: clienteId,
            profissional_id: profissionalId,
            servico_id: servicoId,
            data_hora_inicio: `${dataEscolhida} ${horarioSelecionado}:00`
        };

        const res = await fetch('/api/agendar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (res.status === 201) {
            mensagemStatus = 'Horário reservado com sucesso!';
            horarioSelecionado = null;
            buscarHorarios(); // Atualiza a grade removendo o horário recém-ocupado
        } else {
            const erro = await res.json();
            mensagemStatus = erro.erro || 'Falha ao reservar o horário.';
        }
    }

    onMount(buscarHorarios);
</script>

<div class="cartao-agendamento">
    <h2>Horários em {dataEscolhida}</h2>
    
    <div class="grade-horarios">
        {#each horarios as hora}
            <button 
                class:ativo={horarioSelecionado === hora}
                on:click={() => horarioSelecionado = hora}
            >
                {hora}
            </button>
        {/each}
    </div>

    {#if horarioSelecionado}
        <button class="btn-confirmar" on:click={confirmarAgendamento}>
            Confirmar para as {horarioSelecionado}
        </button>
    {/if}

    {#if mensagemStatus}
        <p class="alerta-status">{mensagemStatus}</p>
    {/if}
</div>

<style>
    .cartao-agendamento {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    h2 {
        margin-top: 0;
        font-size: 1.2rem;
    }

    .grade-horarios {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }

    button {
        padding: 10px;
        border: 1px solid var(--cor-primaria);
        background-color: transparent;
        color: var(--cor-primaria);
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.2s ease-in-out;
    }

    button.ativo, button:active {
        background-color: var(--cor-primaria);
        color: #ffffff;
    }

    .btn-confirmar {
        width: 100%;
        background-color: #1A1A1A;
        color: #ffffff;
        border: none;
        padding: 15px;
        font-size: 1rem;
        cursor: pointer;
        border-radius: 4px;
    }

    .alerta-status {
        margin-top: 15px;
        font-weight: 600;
        color: var(--cor-primaria);
        text-align: center;
    }
</style>