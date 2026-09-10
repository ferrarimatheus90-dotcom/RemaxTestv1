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
import io, os, sys

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
    'q15.html',  # Módulo 6 · Programa de Excelência
    'q16.html',  # ícones, idiomas, tema, marca luminosa e abertura
    'q14.html',  # mapa do escopo (40 frentes) + boot
]

def ler(nome):
    return io.open(os.path.join(AQUI, nome), encoding='utf-8').read()

def main():
    saida = os.path.abspath(sys.argv[1]) if len(sys.argv) > 1 else SAIDA_PADRAO
    html = ''.join(ler(p) for p in PARTES)
    html = html.replace('__LOGO__', 'data:image/png;base64,' + ler('logo.b64').strip())
    html = html.replace('__BASE__', ler('base.json').strip())
    if '__LOGO__' in html or '__BASE__' in html:
        raise SystemExit('placeholder nao substituido')
    io.open(saida, 'w', encoding='utf-8').write(html)
    print('gerado:', saida, str(len(html) // 1024) + ' KB')

if __name__ == '__main__':
    main()
