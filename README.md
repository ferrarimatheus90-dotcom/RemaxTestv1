# Prontos em Rede — protótipo

Protótipo funcional da plataforma Prontos em Rede para a RE/MAX: 6 módulos, 4 níveis de acesso,
tema claro e escuro, português, inglês e espanhol. Desenvolvido por **BMAG SERVIÇOS DIGITAIS**.

O site inteiro é um arquivo só — `index.html` —, sem banco de dados nem servidor: o que cada
usuário faz fica gravado no próprio navegador (`localStorage`).

> **Repositório privado.** A base de demonstração traz nome, CRECI, foto e telefone de corretores
> reais (dados públicos do site da RE/MAX). Não torne este repositório público sem antes trocar
> os telefones em `src/base.json`.

## Contas de teste

Senha de todas: `remax2027`

| Acesso | E-mail |
|---|---|
| RE/MAX Brasil (visão nacional) | brasil@remax.com.br |
| Master regional | master@remax.com.br |
| Gestor da unidade | loja@remax.com.br |
| Associado (corretor) | corretor@remax.com.br |

## Publicar na Hostinger (Git)

1. No hPanel, abra o site e vá em **Avançado → GIT**.
2. Como o repositório é privado, clique em **Gerar chave SSH**, copie a chave e cadastre no GitHub em
   **Settings → Deploy keys → Add deploy key** (só leitura basta).
3. De volta à Hostinger, preencha:
   - Repositório: `git@github.com:ferrarimatheus90-dotcom/RemaxTestv1.git`
   - Branch: `main`
   - Diretório: em branco (instala em `public_html`, que precisa estar vazia — apague o `default.php`).
4. Clique em **Criar** e depois em **Implantar**. O `index.html` da raiz vira a página inicial.

Para atualizar: novo push na `main` e **Implantar** de novo — ou ative a implantação automática,
que a Hostinger faz por webhook no GitHub.

## Como alterar

Edite as partes em `src/` e reconstrua a partir da raiz do repositório:

    python src/build.py index.html

Nunca edite o `index.html` à mão: ele é gerado. O que cada parte faz está em `src/LEIAME.md`.
