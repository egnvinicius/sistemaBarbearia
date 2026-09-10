<script>
    import { onMount } from 'svelte';
    import Agendamento from './components/Agendamento.svelte';
    
    // 1. Inicializamos todas as propriedades para evitar qualquer erro de undefined
    let config = {
        nome_fantasia: 'Carregando...',
        logo_url: '',
        cor_primaria: '#093390',
        cor_fundo: '#F9F9F6'
    };

    onMount(async () => {
        try {
            const res = await fetch('/api/config.php');
            
            // 2. Lemos a resposta como texto bruto antes de converter para JSON
            const textoResposta = await res.text();
            
            // 3. Imprimimos no console para você debugar (F12 no navegador)
            console.log("Status HTTP:", res.status);
            console.log("Resposta bruta do servidor:", textoResposta);

            if (res.ok) {
                // 4. Converte para JSON e atualiza as variáveis
                const dados = JSON.parse(textoResposta);
                config = dados;
                
                document.documentElement.style.setProperty('--cor-primaria', config.cor_primaria);
                document.documentElement.style.setProperty('--cor-fundo', config.cor_fundo);
            } else {
                config.nome_fantasia = 'Erro de Servidor';
            }
        } catch (erro) {
            console.error("Erro no Fetch ou no Parse:", erro);
            config.nome_fantasia = 'Erro de Conexão';
        }
    });
</script>

<main>
    <header>
        <h1>{config.nome_fantasia}</h1>
    </header>
    
    <div class="container">
        <div class="container">
            <Agendamento />
        </div>
    </div>
</main>

<style>
    header {
        background-color: var(--cor-primaria);
        color: white;
        padding: 20px;
        text-align: center;
    }

    h1 {
        margin: 0;
        font-size: 1.5rem;
    }

    .container {
        padding: 20px;
        max-width: 600px;
        margin: 0 auto;
    }
</style>