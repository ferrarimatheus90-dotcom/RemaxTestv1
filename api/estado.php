<?php
/* =======================================================================
   Estado compartilhado da demonstração.

   O protótipo guarda tudo no navegador (localStorage). Para que duas pessoas
   em lugares diferentes vejam a mesma coisa (um comunicado publicado por um
   aparece para o outro), cada ação sobe para cá e os outros navegadores
   puxam de tempos em tempos. Vale último-a-gravar-ganha: é uma demonstração,
   não o banco de produção.

   GET  ?acao=ler&desde=<carimbo>   → {ok, igual:true} se nada mudou, ou {ok, atualizado, estado}
   POST ?acao=gravar  corpo {estado} → grava; devolve {ok, atualizado}
   POST ?acao=zerar                  → apaga (Recarregar demonstração)
   O arquivo fica UMA PASTA ACIMA do public_html, como o prontos-config.php.
   ======================================================================= */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = isset($_GET['q']) && preg_match('/^[a-z0-9-]{1,20}$/', $_GET['q']) ? $_GET['q'] : 'remax';
$arquivo = dirname(__DIR__, 2) . '/prontos-estado-' . $q . '.json';
$acao = $_GET['acao'] ?? 'ler';

function fim($d, $cod = 200) { http_response_code($cod); echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }

// só o próprio site grava (bloqueia scripts de fora); ler é livre
function mesmoSite() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $h) {
        if (!empty($_SERVER[$h])) { $u = parse_url($_SERVER[$h]); return isset($u['host']) && strcasecmp($u['host'], preg_replace('/:\d+$/', '', $host)) === 0; }
    }
    return false;
}

if ($acao === 'ler') {
    if (!is_file($arquivo)) fim(['ok' => true, 'vazio' => true]);
    $mt = (string) filemtime($arquivo) . '.' . filesize($arquivo);
    if (isset($_GET['desde']) && $_GET['desde'] === $mt) fim(['ok' => true, 'igual' => true, 'atualizado' => $mt]);
    $txt = file_get_contents($arquivo);
    header('X-Atualizado: ' . $mt);
    echo '{"ok":true,"atualizado":"' . $mt . '","estado":' . ($txt !== '' ? $txt : 'null') . '}';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !mesmoSite()) fim(['ok' => false, 'erro' => 'Gravação só pelo próprio site.'], 403);

if ($acao === 'zerar') { @unlink($arquivo); fim(['ok' => true]); }

if ($acao === 'gravar') {
    $corpo = file_get_contents('php://input');
    if (strlen($corpo) > 12 * 1024 * 1024) fim(['ok' => false, 'erro' => 'Estado grande demais (limite 12 MB).'], 413);
    $j = json_decode($corpo, true);
    if (!is_array($j) || !isset($j['estado']) || !is_array($j['estado']) || (int) ($j['estado']['v'] ?? 0) !== 4) fim(['ok' => false, 'erro' => 'Estado inválido.'], 400);
    $tmp = $arquivo . '.tmp';
    if (file_put_contents($tmp, json_encode($j['estado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) fim(['ok' => false, 'erro' => 'Não consegui gravar no servidor.'], 500);
    rename($tmp, $arquivo); clearstatcache();
    fim(['ok' => true, 'atualizado' => (string) filemtime($arquivo) . '.' . filesize($arquivo)]);
}

fim(['ok' => false, 'erro' => 'Ação desconhecida.'], 400);
