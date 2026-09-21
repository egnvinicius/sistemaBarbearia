<script>
    import { createEventDispatcher } from 'svelte';
    const dispatch = createEventDispatcher();

    // Modos de tela: 'cliente' | 'admin' | 'cadastro'
    let modo = 'cliente'; 

    // Estado Login
    let inputTelefone = '';
    let inputLogin = '';
    let inputSenha = '';

    // Estado Cadastro
    let regNome = '';
    let regTelefone = '';
    let regLogin = '';
    let regSenha = '';
    let ehFuncionario = false;

    let erro = '';
    let sucesso = '';
    let processando = false;

    function alternarModo(novoModo) {
        modo = novoModo;
        erro = ''; sucesso = '';
    }

    // Acesso sem fricção para Clientes
    async function entrarComoCliente() {
        if (!inputTelefone) return;
        processando = true; erro = '';

        const res = await fetch('/api/identificar_cliente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ telefone: inputTelefone })
        });

        if (res.ok) {
            const dados = await res.json();
            if (dados.status === 'novo_cliente') {
                erro = 'Número não encontrado. Por favor, crie seu cadastro rápido.';
                regTelefone = inputTelefone;
                modo = 'cadastro';
            } else {
                localStorage.setItem('cliente_barbearia', JSON.stringify(dados.cliente));
                dispatch('sucesso_cliente'); // Dispara evento para liberar a agenda
            }
        }
        processando = false;
    }

    // Acesso seguro para Gestão
    async function entrarComoAdmin() {
        processando = true; 
        erro = '';
        
        const res = await fetch('/api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ login: inputLogin, senha: inputSenha })
        });

        if (res.ok) {
            const dados = await res.json();
            localStorage.setItem('admin_token', dados.token);
            dispatch('sucesso_admin');
        } else {
            // AGORA O SVELTE LÊ O ERRO QUE VEM DO PHP
            const dados = await res.json();
            erro = dados.erro || 'Credenciais de administrador inválidas.';
        }
        
        processando = false;
    }

    // Cadastro Inteligente (Cliente ou Admin)
    async function cadastrar() {
        processando = true; erro = ''; sucesso = '';
        
        const payload = { nome: regNome, telefone: regTelefone };
        if (ehFuncionario) {
            payload.login = regLogin;
            payload.senha = regSenha;
        }

        const res = await fetch('/api/cadastrar_usuario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (res.status === 201) {
            if (ehFuncionario) {
                sucesso = 'Cadastro recebido! Aguarde a aprovação do administrador para acessar.';
                modo = 'admin';
            } else {
                sucesso = 'Conta criada com sucesso!';
                inputTelefone = regTelefone;
                modo = 'cliente';
            }
        } else {
            const erroDados = await res.json();
            erro = erroDados.erro || 'Falha ao criar conta.';
        }
        processando = false;
    }
</script>

<div class="login-container">
    <div class="card-login">
        
        {#if sucesso}<p class="alerta sucesso-msg">{sucesso}</p>{/if}

        <!-- TELA DO CLIENTE -->
        {#if modo === 'cliente'}
            <h2>Acesse sua conta</h2>
            <p class="subtitulo">Informe seu WhatsApp para agendar</p>
            
            <div class="campo">
                <input type="tel" bind:value={inputTelefone} placeholder="DDD + Número" on:keydown={(e) => e.key === 'Enter' && entrarComoCliente()} />
            </div>

            {#if erro}<p class="alerta">{erro}</p>{/if}

            <button on:click={entrarComoCliente} disabled={processando}>
                {processando ? 'Verificando...' : 'Entrar'}
            </button>

            <div class="opcoes-rodape">
                <a href="#" on:click|preventDefault={() => alternarModo('cadastro')}>Criar conta grátis</a>
                <a href="#" on:click|preventDefault={() => alternarModo('admin')} class="link-secundario">Acessar como admin</a>
            </div>

        <!-- TELA DO ADMINISTRADOR -->
        {:else if modo === 'admin'}
            <h2>Área Gerencial</h2>
            <p class="subtitulo">Acesso restrito à equipe</p>
            
            <div class="campo">
                <input type="text" bind:value={inputLogin} placeholder="Login" />
            </div>
            
            <div class="campo">
                <input type="password" bind:value={inputSenha} placeholder="Senha" on:keydown={(e) => e.key === 'Enter' && entrarComoAdmin()} />
            </div>

            {#if erro}<p class="alerta">{erro}</p>{/if}

            <button on:click={entrarComoAdmin} disabled={processando}>
                {processando ? 'Autenticando...' : 'Acessar Painel'}
            </button>

            <div class="opcoes-rodape">
                <a href="#" on:click|preventDefault={() => alternarModo('cliente')}>Voltar para agendamento</a>
            </div>

        <!-- TELA DE CADASTRO -->
        {:else}
            <h2>Novo Cadastro</h2>
            
            <div class="campo">
                <label>Nome Completo (Ex: Alexandre José)</label>
                <input type="text" bind:value={regNome} placeholder="Seu nome" />
            </div>

            <div class="campo">
                <label>WhatsApp</label>
                <input type="tel" bind:value={regTelefone} placeholder="(00) 00000-0000" />
            </div>

            <div class="campo-checkbox">
                <input type="checkbox" id="checkFunc" bind:checked={ehFuncionario} />
                <label for="checkFunc">Sou funcionário / administrador</label>
            </div>

            {#if ehFuncionario}
                <div class="campo fade-in">
                    <label>Login de Acesso</label>
                    <input type="text" bind:value={regLogin} placeholder="Nome de usuário" />
                </div>
                <div class="campo fade-in">
                    <label>Senha de Acesso</label>
                    <input type="password" bind:value={regSenha} placeholder="Crie uma senha segura" />
                </div>
            {/if}

            {#if erro}<p class="alerta">{erro}</p>{/if}

            <button on:click={cadastrar} disabled={processando}>
                {processando ? 'Registrando...' : 'Cadastrar'}
            </button>

            <div class="opcoes-rodape">
                <a href="#" on:click|preventDefault={() => alternarModo('cliente')}>Já tenho conta</a>
            </div>
        {/if}

    </div>
</div>

<style>
    .login-container { display: flex; justify-content: center; align-items: center; padding: 40px 20px; }
    .card-login { background: #ffffff; border: 1px solid #d4d4d4; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
    
    h2 { font-family: 'Playfair Display', serif; text-align: center; margin-top: 0; margin-bottom: 5px; color: #1A1A1A; }
    .subtitulo { text-align: center; color: #666; font-size: 0.9rem; margin-bottom: 25px; margin-top: 0; }
    
    .campo { margin-bottom: 20px; display: flex; flex-direction: column; }
    .campo-checkbox { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
    .campo-checkbox label { margin-bottom: 0; cursor: pointer; text-transform: none; font-size: 0.85rem; font-weight: bold; }
    
    label { font-size: 0.75rem; color: #666; text-transform: uppercase; margin-bottom: 8px; }
    input[type="text"], input[type="tel"], input[type="password"] { padding: 12px; border: 1px solid #ccc; font-size: 1rem; outline: none; }
    
    button { width: 100%; padding: 15px; background-color: var(--cor-primaria); color: white; border: none; font-weight: 600; text-transform: uppercase; cursor: pointer; transition: 0.2s; }
    button:disabled { background-color: #ccc; cursor: not-allowed; }
    
    .alerta { color: #8b0000; text-align: center; font-size: 0.9rem; margin-bottom: 15px; }
    .sucesso-msg { color: #2e8b57; font-weight: bold; }
    
    .opcoes-rodape { display: flex; flex-direction: column; gap: 10px; margin-top: 25px; text-align: center; font-size: 0.9rem; }
    .opcoes-rodape a { color: var(--cor-primaria); font-weight: bold; text-decoration: none; }
    .opcoes-rodape a:hover { text-decoration: underline; }
    .link-secundario { color: #666 !important; font-weight: normal !important; }

    .fade-in { animation: fadeIn 0.3s ease-in; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>