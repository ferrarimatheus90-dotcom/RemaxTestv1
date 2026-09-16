# Prontos em Rede — código-fonte do protótipo

Protótipo funcional da plataforma RE/MAX. Desenvolvido por BMAG SERVIÇOS DIGITAIS.
Versão **6** (10/09/2026) — **linguagem Apple**: tipografia **SF Pro** nos aparelhos Apple e
**Inter** com corte óptico nos demais; cartões brancos de canto 18px sobre cinza `#F5F5F7`,
sem borda; botões em pílula (azul para a ação principal, cinza translúcido para as demais);
indicadores no jeito do app Saúde; controles segmentados; chat em bolhas; calendário com o
dia de hoje num círculo vermelho; avisos no formato de notificação do macOS.
A **marca nova da RE/MAX (2025)** abre o site: o pino sobe no céu azul, pousa e a escrita REMAX
desce de baixo dele, formando o logo vertical. O mesmo logo aparece no acesso; o pino, no banner da
home e nas peças. No Estúdio, o
corretor sobe a **foto do imóvel**, que entra no lugar do fundo da peça. Menu lateral só em texto, com contadores vermelhos; ao passar o
mouse num módulo abre um painel com as telas em letra grande e a tela de trás desfoca.
Da v4: tema claro e escuro, três idiomas (pt/en/es). Da v3: redes sociais com provedor,
indicadores de marketing unificados, login por nível, Programa de Excelência e assinatura
BMAG SERVIÇOS DIGITAIS.

## Como reconstruir

```bash
python build.py
```

Gera `../prontos-em-rede-app.html` (arquivo único, ~360 KB, abre com duplo clique).
**Editar as partes desta pasta e reconstruir** — nunca editar o HTML final, que é gerado.

## Versão 3 (11/09/2026) — ajustes da reunião de 10/09

A versão anterior está em `../v2/` e na branch `v2` do repositório. O que mudou, item a item, está em
`../documentos-v3/historico-de-alteracoes-v3.html` (e o PDF ao lado). Em resumo: perfil **Matriz**
(ex-Marketing), perfil **Construtor** (BMAG, uso interno), botão Voltar, direitos reservados BMAG,
Estúdio de Arte no menu, Help Desk, RE/MAX TV e banner na home, comunicados com mídia e avisos por
e-mail/WhatsApp, alçadas N1/N2/N3, importação de usuários por planilha, PEX como módulo, BI digital e
tradicional, Parametrização (nomenclatura por cliente), camada `api/` em PHP e a **versão genérica**:

```bash
python build.py ../demo/index.html --generica     # sem a marca RE/MAX, nomes fictícios, estado separado
```

Na genérica o `build.py` troca a base por nomes fictícios, gera um logo neutro em SVG, marca a
constante `GENERICA` (chave de estado `hub_state_v1`, sem vinheta em vídeo, sem os vídeos da RE/MAX TV)
e a camada de nomenclatura (`aplicarTermos`, em `q19.html`) troca “RE/MAX” por “Rede Exemplo” na tela.

## Os 7 módulos (+ Operação BMAG)

| # | Módulo | Telas |
|---|--------|-------|
| 1 | Brand Center | Guia da marca · Estúdio de Arte · Biblioteca de materiais |
| 2 | Social Hub | Campanhas da rede · Redes sociais · Indicadores de redes sociais |
| 3 | Compliance & Governance | Jornada de adequação · Gestão de jornadas (Matriz) · Fachadas · Aprovações · Usuários e permissões |
| 4 | Communication Center | Comunicados · RE/MAX TV · Pessoas da rede |
| 5 | Support & Success | Help Desk · Chamados e assistente |
| 6 | Intelligence (BI) | Painel da rede · Marketing digital e tradicional |
| 7 | Programa de Excelência | Visão do programa · Regras e pontuação · Ranking e Grand Champion |
| 8 | Operação BMAG (só Construtor) | Parametrização · Registros e auditoria · Implantação e CS · Mapa do escopo |

Atalhos diretos no menu: Início, Estúdio de Arte e Help Desk. A Master de teste é a da regional da
unidade de teste (`MASTER_DEMO = 'm3'`, em `q2.html`), para o fluxo gestor → N1 → N2 fechar na demonstração.

As 40 frentes da minuta da RE/MAX estão mapeadas na tela **Mapa do escopo**
(`q14.html`), com o módulo, a tela e a onda de cada uma: 35 no MVP,
5 na 2ª onda (publicação automatizada no Instagram, IA avançada, ranking e
Grand Champion).

## Estrutura das partes

- `p1.html` — `<head>`, fonte, **tokens** (cores claro/escuro, raios, sombras) e componentes
  base: estrutura, cabeçalho de página, botões, cartões, indicadores, etiquetas, tabelas,
  campos.
- `p2.html` — componentes de módulo: biblioteca, estúdio, linha do tempo, listas, chat,
  calendário, fachadas, permissões, avisos, janela e busca.
- `q1.html` — menu lateral, painel do módulo, menus do topo, marca luminosa, abertura,
  acesso, banner da home e telas específicas (arquivos, provas, filtros, abas, faixas).
- `q16.html` — ícones SVG, dicionário de idiomas, controle de tema, contadores que sobem,
  **`marcaBrilho()`** (o balão da RE/MAX com luz, no acesso e no banner) e a abertura.
- `q2.html` — `<body>`, utilidades, **camada de estado** (`localStorage`,
  chave `pr_state_v4`), personas e upload de anexos.
- `q3.html` — conteúdo do produto: modelos de peça, comunicados, campanhas,
  chamados, FAQ e provedores de redes sociais.
- `q4.html` — definição dos 6 módulos, acesso, navegação (painel do módulo com véu),
  rotas, busca e alertas.
- `q5.html` — motor de arte (desenha a peça em canvas e exporta PNG e PDF) e motor de
  gráficos (linha, barras e rosca em canvas, sem biblioteca).
- `q6.html` a `q13.html` — as telas, na ordem dos módulos. O Início (`q6.html`) traz, abaixo do
  banner, o vídeo oficial "REMAX 2026." do canal da REMAX Brasil no YouTube (`VIDEO_INST`): só a capa
  carrega de início; o player (youtube-nocookie) entra no clique.
- `q15.html` — Módulo 6 · Programa de Excelência (comprovação com foto, validação,
  faixas de bonificação e ranking). Entra no build antes do `q14.html`.
- `q14.html` — mapa das 40 frentes e inicialização (abertura → acesso → aplicação).
- `base.json` — 20 lojas e 180 associados **reais** da capital de São Paulo
  (dados públicos de remax.com.br, coleta de 27/07/2026) + 3 regionais de
  demonstração. Com os 20 gestores, dá os 200 usuários.
- `balao.b64` (o pino), `wordmark-branco.b64` e `wordmark-cor.b64` (a escrita REMAX nova, branca e
  preta) — recortados de `../remax-logo-2025.png` pelo script de recorte. Usados na abertura, no
  acesso e no motor de arte.
- `logo.b64` — a escrita REMAX nova, branca; é a do menu lateral (vira preta no tema claro por filtro),
  da fachada ilustrada e do Guia da marca. No tema claro o menu
  inverte as cores por filtro (`invert + hue-rotate`), o que mantém a barra vermelha.

## O que é real e o que é demonstração

**Real:** as unidades, os associados, CRECI e fotos; e tudo o que o usuário faz
no protótipo (evidências enviadas, aprovações, comunicados, chamados, peças,
agendamentos) — que fica gravado e recalcula adesão, painel, ranking e auditoria.

**Carga de demonstração:** o estado inicial de adesão das unidades, leituras,
chamados e campanhas, para a tela não nascer vazia. A engrenagem no topo mostra
isso ao usuário e permite recarregar a demonstração ou exportar os dados.

## Acesso

A plataforma abre em uma tela de login que pede **e-mail e senha** — não lista mais as contas
nem imprime a senha no rodapé. As contas estão em `const CONTAS` (q4.html), cada uma com a sua
senha; a folha com elas fica fora do repositório.

Uma conta pode ter `expira` (data ISO) e `pessoal:true`. `contaExpirada()` recusa a entrada depois
da data, e `entrarNaPlataforma()` (q14.html) derruba a sessão já aberta quando a data passa.
Conta `pessoal` mostra o próprio nome no topo (via `personas()`, q2.html) e não aparece nem pode
usar o "Trocar de acesso". A conta com que a pessoa entrou fica em `pr_conta`, lida por `contaSessao()`.
O botão de perfil no topo troca entre os acessos permanentes sem sair, e "Sair da conta" volta ao login.

## Tema, idioma, fonte e abertura

- **Tema**: o atributo `data-tema` (claro/escuro) fica no elemento raiz e todas as cores
  são variáveis CSS declaradas em `p1.html`. O seletor no topo grava a escolha em
  `pr_tema` (claro, escuro ou automático). Ao trocar, a rota é redesenhada para os
  gráficos em canvas pegarem as cores novas — é o que a função `atualizaPaleta` faz.
- **Idioma**: gravado em `pr_idioma` (pt, en, es); o dicionário está em `q16.html`.
  A tradução acontece em dois lugares: chamadas `T(...)` nas partes reescritas e
  `aplicarIdioma(raiz)`, que percorre os nós de texto depois de cada render e troca as
  frases presentes no dicionário. Frase nova que precise de tradução: acrescentar a
  chave **exatamente** como aparece na tela. O que não estiver no dicionário permanece
  em português.
- **Fonte**: a SF Pro não pode ser embutida (licença da Apple), então a pilha de fontes
  pede a fonte do sistema primeiro (`-apple-system` — é a SF Pro em iPhone e Mac) e cai
  na **Inter** do Google Fonts no Windows e no Android. A Inter vem com o eixo óptico
  (`opsz`), que aperta o desenho nos títulos grandes, como a SF Pro Display.
- **Vinheta em vídeo**: `splash.mp4` (H.264) e `splash.webm` (VP9, para navegador sem H.264) —
  8,5 s, 1920×1080, sem áudio — abrem o site. Ficam em `prontos-em-rede-src/` e o `build.py`
  copia os dois para o lado do HTML gerado. Aba aberta em segundo plano: o vídeo espera a aba
  aparecer, porque o navegador pausa vídeo sem som em aba escondida. O primeiro quadro vai embutido (`splash-poster.b64`) para
  a tela não piscar. Toca em toda visita — inclusive com "reduzir movimento" ligado no aparelho e ao voltar
  pelo botão Voltar — com o botão "Pular". Se o vídeo não começar em 8 s, entra a
  abertura animada; se o aparelho bloquear o vídeo automático, aparece o botão "Assistir". Celular em pé: o miolo do vídeo é ampliado sobre um fundo da mesma cor.
- **Abertura animada (reserva)**: o céu azul acende, o pino da RE/MAX sobe e pousa, e a escrita REMAX desce de baixo
  dele formando o logo vertical; depois vem o título. Ao terminar, a tela de acesso já está por baixo e
  as duas se cruzam. Como reserva, roda uma vez por navegador (chave `pr_abertura_anim`) e pode ser revista pelo menu da engrenagem,
  em "Ver a abertura". Respeita `prefers-reduced-motion`.

## Notas de manutenção

- O estado é acessado por um wrapper (`LS`) tolerante: se o navegador bloquear
  gravação ao abrir por `file://`, o app avisa uma vez e segue funcionando em
  memória.
- Para testar num navegador: `python -m http.server` nesta pasta não serve —
  o HTML montado fica um nível acima. Sirva a pasta do projeto e use sempre uma
  query nova (`?v=2`) ao recarregar, senão o navegador entrega a versão em cache.
  Trocar só o `#hash` não recarrega a página (a persona não muda).
- Ao mexer nos prazos das etapas (`ETAPAS`, em `q2.html`), confira os textos que
  citam datas no FAQ (`q3.html`) e na busca (`q4.html`).
- Cartões dentro de cartões: `.kpi`, `.faixa` e `.prova` trocam o branco pelo cinza
  (`--surface-2`) quando estão dentro de `.card`, para a borda não sumir.
