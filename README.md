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
| `/v3/` | **Cópia de segurança congelada da versão de 14/09/2026** — a que foi apresentada na reunião com o Bruno. Não recebe alterações. Serve para comparar ou voltar a mostrar o que já estava aprovado. |

## v3.10 — o que mudou depois da reunião de 14/09

- **Intelligence (BI) com submenu por módulo.** Brand Center, Social Hub, Compliance, Centro de
  Comunicação e Programa de Excelência, cada um com os indicadores do seu módulo. Todo indicador
  abre a lista que está por trás dele (drill-down), com exportação para planilha.
- **A rede inteira: 600 unidades** em 27 regionais e cerca de 11 mil associados. As 20 unidades com
  base carregada continuam sendo as operacionais (pessoas, jornada, fachadas, chamados); as demais
  dão a escala nos indicadores, com números de semente fixa, e **não entram no estado sincronizado**.
- **Biblioteca de mídia no Brand Center**: vinhetas de abertura e encerramento, trilhas e spots de
  rádio. Saiu da RE/MAX TV, que era do Centro de Comunicação.
- **O associado também publica**: botão Postar agora na campanha da rede, na conta dele.

## Cópias de segurança

Antes de cada rodada de alterações, a versão que está no ar é congelada em duas frentes:

| Versão | Branch | Endereço congelado |
|---|---|---|
| v2 · antes da reunião de 10/09 | `v2` | — |
| v3 · apresentada em 14/09 | `v3` | `/v3/` |

A pasta `/v3/` é o próprio `index.html` daquele dia, com os caminhos de `api/` e da vinheta
apontando para a raiz, para não duplicar arquivo. Ela mostra um selo no canto inferior esquerdo.

Para voltar o site inteiro a uma versão anterior, implante a branch correspondente pelo hPanel.

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
