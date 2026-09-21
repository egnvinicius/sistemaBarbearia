<script>
    import { onMount } from 'svelte';
    
    let cliente = null; 

    // Filtros de seleção
    let profissionais = [];
    let profissionalId = '';
    let servicos = [];
    let servicoId = '';
    
    let dataEscolhida = new Date().toISOString().split('T')[0]; 
    let dataMinima = dataEscolhida;

    let horarios = [];
    let horarioSelecionado = null;
    let toastVisivel = false;
    let toastMensagem = '';
    let toastSucesso = true;

    async function carregarDadosIniciais() {
        const [resProf, resServ] = await Promise.all([
            fetch('/api/profissionais.php'),
            fetch('/api/servicos.php')
        ]);

        if (resProf.ok) {
            profissionais = await resProf.json();
            if (profissionais.length > 0) profissionalId = profissionais[0].id;
        }

        if (resServ.ok) {
            servicos = await resServ.json();
            if (servicos.length > 0) servicoId = servicos[0].id;
        }
        
        buscarHorarios();
    }

    async function buscarHorarios() {
        if (!profissionalId || !dataEscolhida || !servicoId) return;
        const res = await fetch(`/api/horarios_livres.php?profissional_id=${profissionalId}&data=${dataEscolhida}&servico_id=${servicoId}`);
        if (res.ok) {
            const dados = await res.json();
            horarios = dados.horarios || [];
        }
    }

    function exibirToast(mensagem, sucesso = true) {
        toastMensagem = mensagem;
        toastSucesso = sucesso;
        toastVisivel = true;
        setTimeout(() => toastVisivel = false, 3500);
    }

    async function confirmarAgendamento() {
        // Bloqueio de segurança: impede o admin de tentar agendar sem cliente selecionado
        if (!cliente) {
            exibirToast('Modo Visualização: Faça login via WhatsApp para simular um agendamento real.', false);
            return;
        }

        const payload = {
            cliente_id: cliente.id,
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
            exibirToast('Horário reservado com excelência.', true);
            horarioSelecionado = null;
            buscarHorarios();
        } else {
            const erro = await res.json();
            exibirToast(erro.erro || 'Falha ao reservar o horário.', false);
        }
    }

    onMount(() => {
        const salvo = localStorage.getItem('cliente_barbearia');
        if (salvo) {
            cliente = JSON.parse(salvo);
        }
        carregarDadosIniciais();
    });

    // Removido a exigência da variável cliente nesta validação reativa
    $: if (profissionalId && dataEscolhida && servicoId) {
        buscarHorarios();
        horarioSelecionado = null;
    }
</script>

<div class="cartao-agendamento">
    <!-- Cabeçalho Dinâmico -->
    <div class="cabecalho-cliente">
        <h2 class="titulo-secao">Agenda de Horários</h2>
        <p class="info-cliente">
            {#if cliente}
                Olá, <strong>{cliente.nome.split(' ')[0]}</strong>
            {:else}
                <strong style="color: var(--cor-primaria)">Modo Visualização</strong> (Acesso Admin)
            {/if}
        </p>
    </div>

    <!-- Filtros de Busca -->
    <div class="filtros-container">
        <div class="filtros-linha">
            <div class="campo">
                <label>Profissional</label>
                <select bind:value={profissionalId}>
                    {#each profissionais as prof}
                        <option value={prof.id}>{prof.nome}</option>
                    {/each}
                </select>
            </div>
            <div class="campo">
                <label>Data</label>
                <input type="date" bind:value={dataEscolhida} min={dataMinima} />
            </div>
        </div>
        <div class="campo">
            <label>Serviço</label>
            <select bind:value={servicoId}>
                {#each servicos as serv}
                    <option value={serv.id}>
                        {serv.nome} - R$ {Number(serv.preco).toFixed(2).replace('.', ',')}
                    </option>
                {/each}
            </select>
        </div>
    </div>

    <!-- Grade de Horários -->
    {#if horarios.length > 0}
        <div class="grade-horarios">
            {#each horarios as hora}
                <button class:ativo={horarioSelecionado === hora} on:click={() => horarioSelecionado = hora}>
                    {hora}
                </button>
            {/each}
        </div>
    {:else}
        <p class="mensagem-vazia">Nenhum horário disponível para esta data.</p>
    {/if}

    <!-- Botão de Confirmação -->
    {#if horarioSelecionado}
        <button class="btn-confirmar" on:click={confirmarAgendamento}>
            Confirmar {horarioSelecionado}
        </button>
    {/if}
</div>

{#if toastVisivel}
    <div class="toast" class:erro={!toastSucesso}>{toastMensagem}</div>
{/if}

<style>
    .cartao-agendamento { background-color: #ffffff; border: 1px solid #d4d4d4; padding: 30px; margin-top: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
    .titulo-secao { font-family: var(--fonte-principal, 'Playfair Display', serif); font-size: 1.6rem; color: #1A1A1A; margin-top: 0; margin-bottom: 5px; text-align: center; }
    
    .cabecalho-cliente { display: flex; flex-direction: column; align-items: center; margin-bottom: 25px; }
    .info-cliente { font-size: 0.95rem; color: #555; margin: 0; }
    
    .filtros-container { display: flex; flex-direction: column; gap: 15px; margin-bottom: 40px; width: 100%; }
    .filtros-linha { display: flex; gap: 15px; width: 100%; }
    .campo { flex: 1; display: flex; flex-direction: column; text-align: left; }
    
    label { font-size: 0.75rem; color: #666; margin-bottom: 5px; text-transform: uppercase; }
    input, select { padding: 12px; border: 1px solid #ccc; font-size: 1rem; outline: none; }
    
    .grade-horarios { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 12px; margin-bottom: 25px; }
    button:not(.btn-confirmar) { padding: 12px 0; border: 1px solid #1A1A1A; background-color: transparent; color: #1A1A1A; cursor: pointer; transition: 0.2s;}
    button.ativo { background-color: #1A1A1A; color: #ffffff; }
    
    .btn-confirmar { width: 100%; background-color: var(--cor-primaria); color: #ffffff; border: none; padding: 16px; font-weight: 600; text-transform: uppercase; cursor: pointer;}
    .mensagem-vazia { text-align: center; color: #666; font-style: italic; }
    
    .toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); background-color: #1A1A1A; color: #ffffff; padding: 15px 30px; z-index: 1000; animation: fadeInOut 3.5s ease forwards; }
    .toast.erro { background-color: #8b0000; }
    @keyframes fadeInOut { 0% { opacity: 0; bottom: 10px; } 10% { opacity: 1; bottom: 30px; } 90% { opacity: 1; bottom: 30px; } 100% { opacity: 0; bottom: 10px; } }
</style>