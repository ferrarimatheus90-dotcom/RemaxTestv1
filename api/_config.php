<?php
/* =======================================================================
   Prontos em Rede · camada de integrações (servidor)
   Desenvolvido por BMAG SERVIÇOS DIGITAIS

   As chaves NUNCA ficam neste repositório (ele é público). Elas ficam no
   arquivo prontos-config.php, UMA PASTA ACIMA do public_html — fora do
   alcance da web e fora do que o deploy do GitHub apaga ou sobrescreve.
   Modelo do arquivo: api/prontos-config.exemplo.php
   ======================================================================= */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function responder($dados, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$CFG = null;
foreach ([dirname(__DIR__, 2) . '/prontos-config.php', __DIR__ . '/config.php'] as $arq) {
    if (is_file($arq)) { $CFG = include $arq; break; }
}

function cfg($k, $padrao = '') {
    global $CFG;
    return (is_array($CFG) && isset($CFG[$k]) && $CFG[$k] !== '') ? $CFG[$k] : $padrao;
}

/* só quem tem a chave de operação (perfil Construtor, digitada no navegador dele) aciona as integrações */
function exigirChave() {
    global $CFG;
    if (!is_array($CFG)) responder(['ok' => false, 'configurado' => false, 'erro' => 'Servidor sem o arquivo prontos-config.php.'], 503);
    $enviada = $_SERVER['HTTP_X_PRONTOS_CHAVE'] ?? '';
    $certa = (string) cfg('chave_painel');
    if ($certa === '' || !hash_equals($certa, (string) $enviada)) {
        responder(['ok' => false, 'configurado' => true, 'erro' => 'Chave de operação inválida.'], 401);
    }
}

/* a chamada veio do próprio site? (para ações sem chave, como o aviso de chamado e o estado compartilhado) */
function mesmoSite() {
    $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $h) {
        if (!empty($_SERVER[$h])) { $u = parse_url($_SERVER[$h]); return isset($u['host']) && strcasecmp($u['host'], $host) === 0; }
    }
    return false;
}

function corpoJson() {
    $t = file_get_contents('php://input');
    $j = json_decode($t ?: '{}', true);
    return is_array($j) ? $j : [];
}

/* chamada HTTP simples; devolve [código, json, erro, texto] */
function http($metodo, $url, $cab = [], $corpo = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => $cab,
        CURLOPT_TIMEOUT        => 40,
    ]);
    if ($corpo !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
    $r = curl_exec($ch);
    $cod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro = curl_error($ch);
    curl_close($ch);
    return [$cod, $r === false ? null : json_decode($r, true), $erro, $r];
}
