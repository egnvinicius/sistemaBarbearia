<script>
    import { onMount } from 'svelte';
    import Agendamento from './components/Agendamento.svelte';
    import Admin from './components/Admin.svelte';
    import Login from './components/Login.svelte';
    
    let adminAutenticado = !!localStorage.getItem('admin_token');
    let clienteAutenticado = !!localStorage.getItem('cliente_barbearia');
    
    // Se for admin, por padrão abre a tela de admin. Se não, modoAdmin fica falso.
    let modoAdmin = adminAutenticado; 
    
    let config = {
        nome_fantasia: 'Carregando...',
        logo_url: '',
        cor_primaria: '#1A1A1A',
        cor_fundo: '#F9F9F6',
        fonte_app: 'Arial, sans-serif'
    };

    onMount(async () => {
        try {
            const res = await fetch('/api/config.php');
            if (res.ok) {
                config = await res.json();
                document.documentElement.style.setProperty('--cor-primaria', config.cor_primaria);
                document.documentElement.style.setProperty('--cor-fundo', config.cor_fundo);
                document.documentElement.style.setProperty('--fonte-principal', config.fonte_app);
            }
        } catch (erro) {
            console.error(erro);
        }
    });

    function logoutGeral() {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('cliente_barbearia');
        adminAutenticado = false;
        clienteAutenticado = false;
        modoAdmin = false;
    }
</script>

<main style="font-family: var(--fonte-principal);">
    <header>
        <div class="header-marca">
            {#if config.logo_url}
                <img src={config.logo_url} alt="Logo" class="logo-img" />
            {/if}
            <h1>{config.nome_fantasia}</h1>
        </div>
        
        <div class="acoes-header">
            {#if adminAutenticado}
                <button class="nav-toggle" on:click={() => modoAdmin = !modoAdmin}>
                    {modoAdmin ? 'Ver Agenda' : 'Área Gerencial'}
                </button>
            {/if}

            {#if adminAutenticado || clienteAutenticado}
                <button class="nav-toggle sair" on:click={logoutGeral}>Sair</button>
            {/if}
        </div>
    </header>
    
    <div class="container">
        <!-- Roteamento Condicional -->
        {#if !adminAutenticado && !clienteAutenticado}
            <Login 
                on:sucesso_cliente={() => clienteAutenticado = true}
                on:sucesso_admin={() => { adminAutenticado = true; modoAdmin = true; }} 
            />
        {:else if adminAutenticado && modoAdmin}
            <Admin />
        {:else}
            <Agendamento />
        {/if}
    </div>
</main>

<style>
    header {
        background-color: var(--cor-primaria);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-marca {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .logo-img {
        max-height: 40px;
        border-radius: 4px;
        object-fit: contain;
    }

    h1 { margin: 0; font-size: 1.5rem; }

    .acoes-header {
        display: flex;
        gap: 10px;
    }

    .nav-toggle {
        background: transparent;
        color: white;
        border: 1px solid white;
        padding: 5px 15px;
        cursor: pointer;
        font-size: 0.85rem;
    }

    .sair {
        border-color: #ffcccc;
        color: #ffcccc;
    }

    .container {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
</style>