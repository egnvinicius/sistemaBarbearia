<script>
    import { onMount } from 'svelte';
    import Dashboard from './Dashboard.svelte';

    // Gestão de Profissionais
    let profissionais = [];
    let novoProfissionalNome = '';
    let mensagemSistema = '';

    // Gestão de Ausências
    let ausenciaProfissionalId = '';
    let ausenciaData = '';
    let ausenciaMotivo = '';
    let mensagemAusencia = '';

    // Gestão White-Label
    let configNome = '';
    let configCorPrimaria = '#1A1A1A';
    let configCorFundo = '#F9F9F6';
    let configLogoUrl = '';
    let configFonteApp = 'Arial, sans-serif';
    let mensagemConfig = '';

    // Gestão de Acessos
    let usuariosPendentes = [];

    async function carregarDadosIniciais() {
        // Carrega profissionais
        const resProf = await fetch('/api/profissionais.php');
        if (resProf.ok) {
            profissionais = await resProf.json();
            if (profissionais.length > 0 && !ausenciaProfissionalId) {
                ausenciaProfissionalId = profissionais[0].id;
            }
        }

        // Carrega configurações visuais
        const resConfig = await fetch('/api/config.php');
        if (resConfig.ok) {
            const config = await resConfig.json();
            configNome = config.nome_fantasia;
            configCorPrimaria = config.cor_primaria;
            configCorFundo = config.cor_fundo;
            configLogoUrl = config.logo_url || '';
            configFonteApp = config.fonte_app || 'Arial, sans-serif';
        }

        // Carrega usuários aguardando aprovação
        const resPendentes = await fetch('/api/gerenciar_acessos.php');
        if (resPendentes.ok) {
            usuariosPendentes = await resPendentes.json();
        }
    }

    // ==========================================
    // NOVAS FUNÇÕES DE APROVAÇÃO (COM FEEDBACK)
    // ==========================================
    async function aprovarUsuario(id) {
        const res = await fetch('/api/gerenciar_acessos.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        if (res.ok) {
            alert('Acesso liberado com sucesso!');
            carregarDadosIniciais(); // Atualiza a tela imediatamente
        } else {
            alert('Erro ao processar a aprovação.');
        }
    }

    async function rejeitarUsuario(id) {
        if (!confirm('Rejeitar e excluir esta solicitação permanentemente?')) return;
        
        const res = await fetch('/api/gerenciar_acessos.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        if (res.ok) {
            alert('Solicitação rejeitada com sucesso.');
            carregarDadosIniciais();
        }
    }

    // Demais funções da gestão...
    async function adicionarProfissional() {
        if (!novoProfissionalNome.trim()) return;
        const res = await fetch('/api/gerenciar_profissionais.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome: novoProfissionalNome })
        });
        if (res.ok) {
            novoProfissionalNome = '';
            mensagemSistema = 'Profissional adicionado com sucesso.';
            carregarDadosIniciais();
        }
    }

    async function removerProfissional(id) {
        if (!confirm('Deseja inativar este profissional?')) return;
        const res = await fetch('/api/gerenciar_profissionais.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, ativo: 0 })
        });
        if (res.ok) {
            mensagemSistema = 'Profissional inativado.';
            carregarDadosIniciais();
        }
    }

    async function registrarAusencia() {
        if (!ausenciaData) { mensagemAusencia = 'Selecione uma data.'; return; }
        const res = await fetch('/api/ausencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                profissional_id: ausenciaProfissionalId,
                data_ausencia: ausenciaData,
                motivo: ausenciaMotivo
            })
        });
        if (res.status === 201) {
            mensagemAusencia = 'Folga registrada.';
            ausenciaData = ''; ausenciaMotivo = '';
        } else {
            const erro = await res.json();
            mensagemAusencia = erro.erro || 'Erro ao registrar folga.';
        }
    }

    async function salvarConfiguracoes() {
        const res = await fetch('/api/atualizar_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nome_fantasia: configNome,
                cor_primaria: configCorPrimaria,
                cor_fundo: configCorFundo,
                logo_url: configLogoUrl,
                fonte_app: configFonteApp
            })
        });
        if (res.ok) mensagemConfig = 'Identidade visual atualizada. Recarregue a página.';
    }

    onMount(carregarDadosIniciais);
</script>

<div class="painel-admin">
    <h2>Painel Gerencial</h2>

    <!-- Indicadores -->
    <Dashboard />

    <!-- Quarentena de Acessos -->
    {#if usuariosPendentes.length > 0}
        <div class="card-controle destaque-pendente">
            <h3>Liberação de Acesso (Equipe)</h3>
            <p class="desc-pendente">Novos cadastros de funcionários aguardando sua autorização.</p>
            
            <ul class="lista">
                {#each usuariosPendentes as user}
                    <li class="linha-pendente">
                        <div class="info-user">
                            <strong>{user.nome}</strong> 
                            <span>Login: {user.login} | Tel: {user.telefone}</span>
                        </div>
                        <div class="acoes-pendente">
                            <button class="btn-aprovar" on:click={() => aprovarUsuario(user.id)}>Aprovar Acesso</button>
                            <button class="btn-remover" on:click={() => rejeitarUsuario(user.id)}>Rejeitar</button>
                        </div>
                    </li>
                {/each}
            </ul>
        </div>
    {/if}

    <!-- Identidade Visual -->
    <div class="card-controle">
        <h3>Identidade Visual (White-Label)</h3>
        <div class="form-vertical">
            <input type="text" bind:value={configNome} placeholder="Nome Fantasia" />
            <input type="text" bind:value={configLogoUrl} placeholder="Link da Logo (Ex: https://site.com/logo.png)" />
            
            <div class="form-linha">
                <div class="campo-cor">
                    <label>Tipografia do App</label>
                    <select bind:value={configFonteApp}>
                        <option value="Arial, sans-serif">Padrão (Arial)</option>
                        <option value="'Playfair Display', serif">Clássica (Playfair Display)</option>
                        <option value="'Montserrat', sans-serif">Moderna (Montserrat)</option>
                    </select>
                </div>
                <div class="campo-cor">
                    <label>Cor Primária</label>
                    <input type="color" bind:value={configCorPrimaria} />
                </div>
                <div class="campo-cor">
                    <label>Cor de Fundo</label>
                    <input type="color" bind:value={configCorFundo} />
                </div>
            </div>
            <button class="btn-acao" on:click={salvarConfiguracoes}>Salvar Identidade</button>
        </div>
        {#if mensagemConfig}<p class="alerta">{mensagemConfig}</p>{/if}
    </div>
    
    <!-- Equipe -->
    <div class="card-controle mt-20">
        <h3>Equipe</h3>
        <div class="form-linha">
            <input type="text" bind:value={novoProfissionalNome} placeholder="Nome do novo barbeiro..." />
            <button class="btn-acao" on:click={adicionarProfissional}>Adicionar</button>
        </div>
        {#if mensagemSistema}<p class="alerta">{mensagemSistema}</p>{/if}

        <ul class="lista">
            {#each profissionais as prof}
                <li>
                    <span>{prof.nome}</span>
                    <button class="btn-remover" on:click={() => removerProfissional(prof.id)}>Remover</button>
                </li>
            {/each}
        </ul>
    </div>

    <!-- Ausências -->
    <div class="card-controle mt-20">
        <h3>Registrar Folga / Ausência</h3>
        <div class="form-vertical">
            <div class="form-linha">
                <select bind:value={ausenciaProfissionalId}>
                    {#each profissionais as prof}
                        <option value={prof.id}>{prof.nome}</option>
                    {/each}
                </select>
                <input type="date" bind:value={ausenciaData} />
            </div>
            
            <textarea 
                bind:value={ausenciaMotivo} 
                placeholder="Motivo (Opcional) - Ex: Atestado médico, manutenção de equipamentos..." 
                rows="2"
            ></textarea>
            
            <button class="btn-acao btn-bloquear" on:click={registrarAusencia}>Bloquear Agenda</button>
        </div>
        {#if mensagemAusencia}<p class="alerta">{mensagemAusencia}</p>{/if}
    </div>
</div>

<style>
    .painel-admin { padding: 20px; max-width: 800px; margin: 0 auto; padding-bottom: 50px; }
    h2 { font-family: 'Playfair Display', serif; margin-bottom: 20px; }
    h3 { font-size: 1.1rem; color: #333; margin-top: 0; margin-bottom: 15px; }

    .card-controle { background: #ffffff; border: 1px solid #d4d4d4; padding: 20px; border-radius: 4px; box-sizing: border-box; }
    .mt-20 { margin-top: 20px; }

    /* Estilos Especiais da Quarentena */
    .destaque-pendente { border-left: 4px solid #cc8500; margin-bottom: 20px; }
    .desc-pendente { font-size: 0.85rem; color: #666; margin-bottom: 15px; }
    .linha-pendente { flex-direction: column; align-items: flex-start !important; gap: 15px; }
    .info-user span { color: #666; font-size: 0.85rem; margin-left: 10px; }
    .acoes-pendente { display: flex; gap: 10px; }
    .btn-aprovar { background-color: #2e8b57; color: white; border: none; padding: 8px 15px; cursor: pointer; font-weight: bold; }
    .btn-aprovar:hover { background-color: #246b43; }

    .form-linha { display: flex; gap: 10px; margin-bottom: 15px; width: 100%; }
    .form-vertical { display: flex; flex-direction: column; gap: 15px; }
    
    .campo-cor { flex: 1; display: flex; flex-direction: column; gap: 5px; }
    .campo-cor label { font-size: 0.8rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
    
    input[type="text"], input[type="date"], select, textarea { flex: 1; padding: 10px; border: 1px solid #ccc; font-family: inherit; outline: none; box-sizing: border-box; }
    input[type="color"] { width: 100%; height: 45px; padding: 2px; border: 1px solid #ccc; cursor: pointer; }
    textarea { width: 100%; resize: vertical; }

    .btn-acao { padding: 10px 20px; background-color: #1A1A1A; color: white; border: none; cursor: pointer; font-weight: 600; white-space: nowrap; transition: 0.2s; }
    .btn-acao:hover { background-color: #333; }
    .btn-bloquear { align-self: flex-end; }

    .lista { list-style: none; padding: 0; margin: 0; }
    .lista li { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #eee; }
    
    .btn-remover { background-color: transparent; color: #8b0000; border: 1px solid #8b0000; padding: 6px 12px; font-size: 0.85rem; cursor: pointer; }
    .btn-remover:hover { background-color: #8b0000; color: white; }

    .alerta { color: var(--cor-primaria); font-weight: bold; font-size: 0.9rem; margin-top: 15px;}
</style>