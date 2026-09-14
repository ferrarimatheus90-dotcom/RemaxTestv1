<?php
/* =======================================================================
   MODELO do arquivo de configuração das integrações.

   1. Copie este arquivo com o nome  prontos-config.php
   2. Coloque-o UMA PASTA ACIMA do public_html na Hostinger
      (Gerenciador de Arquivos → a pasta que contém o public_html).
      Lá ele não é acessível pela web e o deploy do GitHub não o apaga.
   3. Preencha os valores. NUNCA suba o arquivo preenchido para o GitHub.
   ======================================================================= */
return [
    // senha que o perfil Construtor digita em Parametrização para liberar os testes
    'chave_painel'      => 'troque-por-uma-frase-longa-e-secreta',

    // publicação nas redes (bundle.social · Configurações → API)
    'bundle_api_key'    => '',   // a chave criada em Organização → Chaves de API (pode ser pk_live_... ou um código com traços)
    'bundle_team_id'    => '',   // opcional: vazio usa o primeiro time da organização

    // e-mail — opção 1: pela conta de e-mail do domínio (hPanel → E-mails); só o remetente
    'email_remetente'   => '',   // ex.: avisos@connectagendapro.com
    'email_nome'        => 'Prontos em Rede',
    // e-mail — opção 2: por uma conta Gmail/Outlook com SENHA DE APP (myaccount.google.com/apppasswords)
    'smtp_host'         => 'smtp.gmail.com',
    'smtp_port'         => 465,
    'smtp_user'         => '',   // ex.: seunome@gmail.com
    'smtp_pass'         => '',   // a senha de app de 16 letras, sem espaços

    // WhatsApp Cloud API (Meta for Developers → WhatsApp → Configuração da API)
    'whatsapp_token'    => '',   // token permanente do usuário do sistema
    'whatsapp_phone_id' => '',   // Phone number ID
];
