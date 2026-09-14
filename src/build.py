# -*- coding: utf-8 -*-
"""
Monta o arquivo único do protótipo Prontos em Rede.

    python build.py              -> grava ../prontos-em-rede-app.html
    python build.py index.html   -> grava no caminho indicado
                                    (no repositório: python src/build.py index.html)

Lê as partes desta pasta na ordem abaixo, injeta o logotipo (base64) e a base
de dados da rede (base.json) e grava o HTML final.

Regra: SEMPRE editar as partes e reconstruir. Nunca editar o HTML final.
"""
import io, os, sys, shutil, json, base64

AQUI = os.path.dirname(os.path.abspath(__file__))
SAIDA_PADRAO = os.path.join(os.path.dirname(AQUI), 'prontos-em-rede-app.html')

PARTES = [
    'p1.html',   # tokens de cor, shell, componentes base
    'p2.html',   # componentes de módulo (peças, timeline, chat, calendário…)
    'q1.html',   # menu, painel do módulo, abertura, acesso, banner e telas
    'q2.html',   # <body>, utilidades, estado persistente, personas, anexos
    'q3.html',   # conteúdo do produto (modelos, comunicados, campanhas, FAQ, provedores)
    'q4.html',   # os 6 módulos, navegação, rotas, busca, alertas, blocos
    'q5.html',   # motor de arte em canvas + exportação PNG/PDF + gráficos
    'q6.html',   # Início (4 papéis)
    'q7.html',   # Módulo 1 · Brand Center
    'q8.html',   # Módulo 2 · Estúdio de peças
    'q9.html',   # Módulo 2 · Prontos em Rede + redes sociais e agendamentos
    'q10.html',  # Módulo 3 · jornada, fachadas, aprovações
    'q11.html',  # Módulo 4 · comunicados  +  Módulo 3 · usuários e registros
    'q12.html',  # Módulo 5 · atendimento e implantação
    'q13.html',  # Módulo 6 · painel, indicadores de marketing e ranking
    'q15.html',  # Módulo 7 · Programa de Excelência
    'q17.html',  # v3 · estado novo, RE/MAX TV e banner da home
    'q18.html',  # v3 · Help Desk, pessoas da rede, perfil, aviso ao entrar
    'q19.html',  # v3 · jornadas, alçadas, regras do PEX, nomenclatura por cliente
    'q20.html',  # v3 · BI (marketing digital e tradicional) e Parametrização
    'q23.html',  # v3.10 · biblioteca de mídia no Brand Center e Postar agora do associado
    'q22.html',  # v3.10 · a rede nacional (600 unidades) para os indicadores
    'q21.html',  # v3.10 · BI por módulo (submenu do Intelligence) com detalhamento
    'q16.html',  # ícones, idiomas, tema, marca luminosa e abertura
    'q14.html',  # mapa do escopo (40 frentes) + boot
]

def ler(nome):
    return io.open(os.path.join(AQUI, nome), encoding='utf-8').read()

# ---------- versão genérica: sem a marca RE/MAX, com nomes fictícios (para apresentar a outras empresas) ----------
NOMES = ['Ana','Bruno','Carla','Diego','Elisa','Felipe','Gabriela','Henrique','Isabela','João','Karina','Lucas','Mariana','Nicolas',
         'Olívia','Paulo','Renata','Samuel','Tatiana','Vinícius','Yasmin','Rodrigo','Letícia','Marcelo','Priscila','Thiago','Camila','André','Beatriz','Fábio']
SOBRENOMES = ['Andrade','Barbosa','Cardoso','Duarte','Esteves','Freitas','Gomes','Holanda','Jardim','Lacerda','Moraes','Nogueira',
              'Oliveira','Pacheco','Queiroz','Ribeiro','Siqueira','Teixeira','Vasconcelos','Xavier','Zanetti','Moura','Campos']
def base_generica():
    b = json.loads(ler('base.json'))
    regioes = ['Regional Norte', 'Regional Centro', 'Regional Sul', 'Regional Leste', 'Regional Oeste']
    for i, m in enumerate(b['masters']):
        m['nome'] = regioes[i % len(regioes)]; m['area'] = 'Região de demonstração'
    vistos = {}
    for l in b['lojas']:
        nome = 'Unidade ' + (l.get('bairro') or 'Centro')
        vistos[nome] = vistos.get(nome, 0) + 1
        l['nome'] = nome if vistos[nome] == 1 else nome + ' ' + str(vistos[nome])
        l['endereco'] = ''
    for i, c in enumerate(b['corretores']):
        c['nome'] = NOMES[(i * 7) % len(NOMES)] + ' ' + SOBRENOMES[(i * 11) % len(SOBRENOMES)] + ' ' + SOBRENOMES[(i * 5 + 3) % len(SOBRENOMES)]
        c['creci'] = '%06d' % (100000 + i * 37); c['foto'] = ''; c['tel'] = '(11) 9%04d-%04d' % (8000 + i, (i * 53) % 10000)
    return json.dumps(b, ensure_ascii=False)
def svg64(svg):
    return 'data:image/svg+xml;base64,' + base64.b64encode(svg.encode('utf-8')).decode('ascii')
PINO_GEN = ("<svg xmlns='http://www.w3.org/2000/svg' width='389' height='441' viewBox='0 0 100 113'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'>"
            "<stop offset='0' stop-color='#5B9BFF'/><stop offset='1' stop-color='#1B3F9E'/></linearGradient></defs>"
            "<path d='M50 3c25 0 45 19 45 44 0 27-28 47-45 63C33 94 5 74 5 47 5 22 25 3 50 3z' fill='url(#g)'/><circle cx='50' cy='46' r='16' fill='#fff'/></svg>")
def escrita(cor):
    return ("<svg xmlns='http://www.w3.org/2000/svg' width='358' height='80' viewBox='0 0 358 80'><text x='179' y='55' text-anchor='middle' "
            "font-family='Arial, Helvetica, sans-serif' font-size='40' font-weight='700' letter-spacing='2' textLength='340' lengthAdjust='spacingAndGlyphs' fill='%s'>REDE EXEMPLO</text></svg>" % cor)

def main():
    generica = '--generica' in sys.argv
    args = [a for a in sys.argv[1:] if not a.startswith('--')]
    saida = os.path.abspath(args[0]) if args else SAIDA_PADRAO
    os.makedirs(os.path.dirname(saida), exist_ok=True)
    html = ''.join(ler(p) for p in PARTES if os.path.exists(os.path.join(AQUI, p)))
    html = html.replace('__GENERICA__', 'true' if generica else 'false')
    if generica:
        html = html.replace('<title>Prontos em Rede</title>', '<title>Hub da Rede · demonstração</title>')
        html = html.replace('__LOGO__', svg64(escrita('#ffffff'))).replace('__BASE__', base_generica())
        html = html.replace('__BALAO__', svg64(PINO_GEN)).replace('__WORD_BRANCO__', svg64(escrita('#ffffff'))).replace('__WORD_COR__', svg64(escrita('#1D1D1F')))
    html = html.replace('__LOGO__', 'data:image/png;base64,' + ler('logo.b64').strip())
    html = html.replace('__BASE__', ler('base.json').strip())
    for marca, arq in (('__BALAO__', 'balao.b64'), ('__WORD_BRANCO__', 'wordmark-branco.b64'), ('__WORD_COR__', 'wordmark-cor.b64')):
        html = html.replace(marca, 'data:image/png;base64,' + ler(arq).strip())
    html = html.replace('__SPLASH_POSTER__', 'data:image/jpeg;base64,' + ler('splash-poster.b64').strip())
    if any(m in html for m in ('__LOGO__', '__BASE__', '__BALAO__', '__WORD_BRANCO__', '__WORD_COR__', '__SPLASH_POSTER__', '__GENERICA__')):
        raise SystemExit('placeholder nao substituido')
    io.open(saida, 'w', encoding='utf-8').write(html)
    for nome in (() if generica else ('splash.mp4', 'splash.webm')):  # a vinheta (com a marca) viaja ao lado do HTML
        video = os.path.join(AQUI, nome)
        if os.path.exists(video):
            shutil.copy2(video, os.path.join(os.path.dirname(saida), nome))
    print('gerado:', saida, str(len(html) // 1024) + ' KB')

if __name__ == '__main__':
    main()
