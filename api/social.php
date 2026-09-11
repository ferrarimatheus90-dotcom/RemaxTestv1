<?php
/* =======================================================================
   Publicação nas redes sociais (Social Hub) — via API de publicação.
   Fornecedor atual: bundle.social (https://api.bundle.social/api/v1).
   O cliente final nunca vê o fornecedor: a plataforma fala com esta camada.

   GET  ?acao=ping      → responde se o servidor está configurado (sem chave)
   GET  ?acao=status    → time e contas conectadas              (chave)
   POST ?acao=publicar  → {legenda, imagem (data URL ou URL), quando?}  (chave)
   ======================================================================= */
require __DIR__ . '/_config.php';

$acao = $_GET['acao'] ?? 'ping';
if ($acao === 'ping') {
    responder(['ok' => true, 'configurado' => is_array($CFG), 'servico' => 'publicacao']);
}
exigirChave();

$chave = cfg('bundle_api_key');
$time  = cfg('bundle_team_id');
if (!$chave || !$time) {
    responder(['ok' => false, 'configurado' => false, 'erro' => 'Falta bundle_api_key ou bundle_team_id no prontos-config.php.'], 503);
}
$base = 'https://api.bundle.social/api/v1';
$cab  = ['x-api-key: ' . $chave, 'Accept: application/json'];

if ($acao === 'status') {
    [$cod, $j, $erro] = http('GET', $base . '/team/' . rawurlencode($time), $cab);
    if ($cod !== 200) {
        responder(['ok' => false, 'erro' => 'A API de publicação respondeu HTTP ' . $cod . ($erro ? ' (' . $erro . ')' : '') . '.'], 502);
    }
    $contas = [];
    foreach (($j['socialAccounts'] ?? []) as $c) {
        $contas[] = ['tipo' => $c['type'] ?? '', 'nome' => $c['username'] ?? ($c['displayName'] ?? '')];
    }
    responder(['ok' => true, 'msg' => 'Conectado ao time ' . ($j['name'] ?? $time) . ' · ' . count($contas) . ' contas conectadas.', 'contas' => $contas]);
}

if ($acao === 'publicar') {
    $d = corpoJson();
    $legenda = mb_substr(trim((string) ($d['legenda'] ?? '')), 0, 2000);
    $imagem  = (string) ($d['imagem'] ?? '');
    if ($imagem === '') responder(['ok' => false, 'erro' => 'O Instagram exige imagem ou vídeo: envie a peça junto.'], 400);

    // 1) a peça vai para a biblioteca de mídia do fornecedor
    if (strpos($imagem, 'data:image/') === 0) {
        [$meta, $b64] = explode(',', $imagem, 2);
        $png = strpos($meta, 'png') !== false;
        $tmp = tempnam(sys_get_temp_dir(), 'peca');
        file_put_contents($tmp, base64_decode($b64));
        $arquivo = new CURLFile($tmp, $png ? 'image/png' : 'image/jpeg', $png ? 'peca.png' : 'peca.jpg');
        [$cod, $j] = http('POST', $base . '/upload', $cab, ['teamId' => $time, 'file' => $arquivo]);
        @unlink($tmp);
    } else {
        [$cod, $j] = http('POST', $base . '/upload/from-url', array_merge($cab, ['Content-Type: application/json']),
                          json_encode(['teamId' => $time, 'url' => $imagem]));
    }
    $uploadId = $j['id'] ?? ($j['uploadId'] ?? null);
    if ($cod < 200 || $cod >= 300 || !$uploadId) {
        responder(['ok' => false, 'erro' => 'Falha ao enviar a peça (HTTP ' . $cod . ').', 'detalhe' => $j], 502);
    }

    // 2) agenda o post (padrão: daqui a 2 minutos)
    $quando = !empty($d['quando']) ? strtotime((string) $d['quando']) : time() + 120;
    $post = [
        'teamId' => $time,
        'title' => mb_substr($legenda !== '' ? $legenda : 'Publicação da plataforma', 0, 80),
        'postDate' => gmdate('Y-m-d\TH:i:s.000\Z', $quando),
        'status' => 'SCHEDULED',
        'socialAccountTypes' => ['INSTAGRAM'],
        'data' => ['INSTAGRAM' => ['type' => 'POST', 'text' => $legenda, 'uploadIds' => [$uploadId]]],
    ];
    [$cod, $j] = http('POST', $base . '/posts', array_merge($cab, ['Content-Type: application/json']), json_encode($post));
    if ($cod < 200 || $cod >= 300) {
        responder(['ok' => false, 'erro' => 'A API recusou a publicação (HTTP ' . $cod . ').', 'detalhe' => $j], 502);
    }
    responder(['ok' => true, 'msg' => 'Publicação agendada para ' . date('d/m/Y H:i', $quando) . '.', 'id' => $j['id'] ?? null]);
}

responder(['ok' => false, 'erro' => 'Ação desconhecida.'], 400);
