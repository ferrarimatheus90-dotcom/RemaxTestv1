# Prontos em Rede — protótipo

Protótipo funcional da plataforma Prontos em Rede para a RE/MAX: 7 módulos, 5 perfis de acesso,
tema claro e escuro, português, inglês e espanhol. Desenvolvido por **BMAG SERVIÇOS DIGITAIS**.
© 2026 BMAG Serviços Digitais. Todos os direitos reservados.

O site é um arquivo só — `index.html` —, sem banco de dados: o que cada usuário faz fica gravado no
próprio navegador (`localStorage`). As integrações reais (publicação no Instagram, e-mail e WhatsApp)
ficam na pasta `api/`, em PHP, e só funcionam no servidor, com as chaves configuradas (veja abaixo).

> A base de demonstração traz nome, CRECI, foto e telefone de corretores reais, tirados das páginas
> públicas da RE/MAX. O login do protótipo é só de tela: quem abre o site consegue ver esses dados.

## Contas de teste

Senha de todas: `Rede@2026`

| Acesso | E-mail |
|---|---|
| Matriz RE/MAX Brasil (visão nacional) | matriz@prontosemrede.test *(o antigo brasil@ continua entrando)* |
| Master Regional | master@prontosemrede.test |
| Gestor da unidade | loja@prontosemrede.test |
| Associado (corretor) | corretor@prontosemrede.test |
| Construtor · BMAG (uso interno: parametrização, auditoria, implantação) | construtor@prontosemrede.test |

## Versões no ar

| Endereço | O que é |
|---|---|
| `/` | Prontos em Rede, com a marca RE/MAX (`index.html`) |
| `/demo/` | Demonstração genérica, sem a marca RE/MAX e com nomes fictícios — para apresentar a outras empresas (`demo/index.html`). Os dados dela ficam separados dos da versão RE/MAX. |

A versão anterior a esta (v2) está guardada na branch `v2`.

## Publicar na Hostinger

1. No hPanel, abra o site e escolha **Implante de GitHub**.
2. Conecte a conta do GitHub e escolha o repositório **RemaxTestv1**, branch **main**, diretório `public_html`.
3. Clique em **Implantar**. Depois de cada push na `main`, a Hostinger atualiza sozinha em cerca de um minuto.

Para voltar à versão anterior: escolha a branch `v2` e implante.

A vinheta de abertura são os arquivos `splash.mp4` e `splash.webm`, ao lado do `index.html`.
O `.htaccess` ensina a hospedagem a servir o WebM como vídeo, faz o navegador sempre conferir se há
versão nova da página e bloqueia o acesso direto a arquivos de configuração.

## Integrações (publicação, e-mail e WhatsApp)

A plataforma chama `api/social.php` (publicação pela bundle.social) e `api/notificar.php`
(e-mail pelo servidor e WhatsApp Cloud API). **As chaves nunca entram neste repositório**, que é público:

1. Copie `api/prontos-config.exemplo.php` com o nome `prontos-config.php`.
2. Preencha as chaves (bundle.social, e-mail do domínio, WhatsApp Cloud) e uma `chave_painel` secreta.
3. Suba o arquivo para **a pasta acima do `public_html`** pelo Gerenciador de Arquivos da Hostinger —
   lá ele não é acessível pela web e o deploy do GitHub não o apaga.
4. Entre como Construtor → Operação BMAG → **Parametrização**, digite a `chave_painel` e use
   “Testar conexão”, “Publicar teste”, “Enviar e-mail de teste” e “Enviar WhatsApp de teste”.

Para conferir se o servidor está pronto: `https://seudominio/api/social.php?acao=ping`.

**Estado compartilhado:** `api/estado.php` guarda o estado da demonstração em `prontos-estado-remax.json`
(e `-demo.json` para a versão genérica), na mesma pasta do `prontos-config.php`. Cada navegador envia o que
faz e confere a cada 8 s; “Recarregar demonstração” zera para todos. Só o próprio site consegue gravar.

**Aviso de chamado:** ao abrir um chamado, `api/notificar.php?acao=chamado` manda e-mail para `email_suporte`
(ou, se vazio, para o `smtp_user`) e WhatsApp para `whatsapp_suporte` quando a Cloud API estiver configurada.

## Documentos

- `docs/Guia-de-Apresentacao-Prontos-em-Rede-v3.pdf` — o sistema inteiro, tela por tela, com roteiro de apresentação e perguntas prováveis.
- `docs/Manual-do-Usuario-Prontos-em-Rede-v3.pdf` — hierarquia e permissões de cada perfil.
- `docs/Tutorial-de-Testes-Prontos-em-Rede-v3.pdf` — roteiro de testes, perfil por perfil.
- `docs/Requisitos-de-Indicadores-Prontos-em-Rede-v3.pdf` — definição, fórmula e fonte de cada indicador.
- `docs/Historico-de-Alteracoes-Prontos-em-Rede-v3.pdf` — o que mudou na versão 3.
- `docs/arquitetura-prontos-em-rede.html` — desenho da arquitetura (gerado com o archify).

## Como alterar

Edite as partes em `src/` e reconstrua a partir da raiz do repositório:

    python src/build.py index.html
    python src/build.py demo/index.html --generica

Nunca edite os `index.html` à mão: eles são gerados. O que cada parte faz está em `src/LEIAME.md`.
