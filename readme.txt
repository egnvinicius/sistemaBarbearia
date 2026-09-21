# Barbearia App - Sistema de Agendamento White-Label

Sistema leve de agendamento desenvolvido para barbearias, com suporte a multi-tenant (configurações visuais personalizadas por cliente), prevenção de double-booking e automação de lembretes.

**Tecnologias Utilizadas**
* Frontend: Svelte (Vite)
* Backend: PHP 8+ (PDO)
* Banco de Dados: MySQL/MariaDB
* Automação: Cron Job (PHP)

**Como Executar Localmente**

1. **Banco de Dados**
   * Inicie o Apache e o MySQL (XAMPP/WAMP).
   * Acesse o phpMyAdmin e execute os scripts SQL de criação para gerar o banco `barbearia_app` e popular os dados iniciais.

2. **Backend (API)**
   * Mova a pasta do projeto para `htdocs` (XAMPP) ou `www` (WAMP).
   * Verifique as credenciais de acesso local no arquivo `api/db.php`.

3. **Frontend (Svelte)**
   * Navegue até a pasta `front-app` pelo terminal.
   * Execute `npm install` para instalar as dependências.
   * Execute `npm run dev` para iniciar o servidor de desenvolvimento com Hot Module Replacement (HMR).

4. **Automação de Mensagens (WhatsApp)**
   * Para testar o script de varredura e envio de lembretes no ambiente local, acesse a rota `http://localhost/barbearia-app/api/whatsapp_cron.php` pelo navegador.
   * Em produção, aponte um Cron Job no servidor para executar este arquivo a cada 10 minutos.