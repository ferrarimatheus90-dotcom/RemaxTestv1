# Prontos em Rede — protótipo

Protótipo funcional da plataforma Prontos em Rede para a RE/MAX: 6 módulos, 4 níveis de acesso,
tema claro e escuro, português, inglês e espanhol. Desenvolvido por **BMAG SERVIÇOS DIGITAIS**.

O site inteiro é um arquivo só — `index.html` —, sem banco de dados nem servidor: o que cada
usuário faz fica gravado no próprio navegador (`localStorage`).

> A base de demonstração traz nome, CRECI, foto e telefone de corretores reais, tirados das páginas
> públicas da RE/MAX. O login do protótipo é só de tela: quem abre o site consegue ver esses dados.

## Contas de teste

Senha de todas: `remax2027`

| Acesso | E-mail |
|---|---|
| RE/MAX Brasil (visão nacional) | brasil@remax.com.br |
| Master regional | master@remax.com.br |
| Gestor da unidade | loja@remax.com.br |
| Associado (corretor) | corretor@remax.com.br |

## Publicar na Hostinger

1. No hPanel, abra o site e escolha **Implante de GitHub**.
2. Conecte a conta do GitHub e escolha o repositório **RemaxTestv1**.
3. Branch: **main**.
4. Diretório: `public_html` publica na raiz do domínio. Para não misturar com um site que já exista
   no domínio, use **Alterar** e aponte para uma pasta vazia — por exemplo `public_html/remax`, que
   abre em `seudominio.com/remax` — ou para um subdomínio.
5. Clique em **Implantar**. O `index.html` da raiz vira a página inicial.

Para atualizar: novo push na `main` e implantar de novo.

## Como alterar

Edite as partes em `src/` e reconstrua a partir da raiz do repositório:

    python src/build.py index.html

Nunca edite o `index.html` à mão: ele é gerado. O que cada parte faz está em `src/LEIAME.md`.
