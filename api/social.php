<?php
/* =======================================================================
   Publicação nas redes sociais (Social Hub) — via API de publicação.
   Fornecedor atual: bundle.social (https://api.bundle.social/api/v1).
   O cliente final nunca vê o fornecedor: a plataforma fala com esta camada.

   GET  ?acao=ping      → responde se o servidor está configurado (sem chave)
   GET  ?acao=status    → time e contas conectadas              (chave)
   POST ?acao=conectar  → {voltar} → link do portal de conexão do Instagram  (chave)
   POST ?acao=publicar  → {legenda, imagem (data URL ou URL), quando?}  (chave)
   ======================================================================= */
require __DIR__ . '/_config.php';

$acao = $_GET['acao'] ?? 'ping';
if ($acao === 'ping') {
    responder(['ok' => true, 'configurado' => is_array($CFG), 'servico' => 'publicacao']);
}
exigirChave();

$chave = cfg('bundle_api_key');
if (!$chave) {
    responder(['ok' => false, 'configurado' => false, 'erro' => 'Falta bundle_api_key no prontos-config.php.'], 503);
}
$base = 'https://api.bundle.social/api/v1';
$cab  = ['x-api-key: ' . $chave, 'Accept: application/json'];
$cabJson = array_merge($cab, ['Content-Type: application/json']);
$msgErro = function ($j) { return is_array($j) ? ($j['message'] ?? ($j['error'] ?? '')) : ''; };

// o time: o do prontos-config.php ou, se estiver vazio, o primeiro time da organização
$time = cfg('bundle_team_id');
$nomeTime = '';
if (!$time) {
    [$cod, $j, $erro] = http('GET', $base . '/team/?limit=10', $cab);          // lista os times (a barra final faz parte do caminho)
    if ($cod === 404) [$cod, $j, $erro] = http('GET', $base . '/organization/', $cab);
    if ($cod === 401 || $cod === 403) responder(['ok' => false, 'erro' => 'A chave da API foi recusada (HTTP ' . $cod . '). Confira bundle_api_key.'], 502);
    if ($cod !== 200) responder(['ok' => false, 'erro' => 'A API de publicação respondeu HTTP ' . $cod . ' ao listar os times.' . ($erro ? ' (' . $erro . ')' : '')], 502);
    $lista = isset($j['items']) ? $j['items'] : (isset($j['teams']) ? $j['teams'] : (isset($j['data']) ? $j['data'] : (is_array($j) && isset($j[0]) ? $j : [])));
    $lista = array_values(array_filter((array) $lista, function ($t) { return empty($t['deletedAt']); }));
    if (!$lista || empty($lista[0]['id'])) responder(['ok' => false, 'erro' => 'A chave é válida, mas a organização não tem nenhum time. Crie um time no painel.'], 502);
    $time = $lista[0]['id']; $nomeTime = $lista[0]['name'] ?? '';
}

if ($acao === 'status') {
    [$cod, $j, $erro] = http('GET', $base . '/team/' . rawurlencode($time), $cab);
    if ($cod === 401 || $cod === 403) responder(['ok' => false, 'erro' => 'A chave da API foi recusada (HTTP ' . $cod . '). Confira bundle_api_key.'], 502);
    if ($cod === 404) responder(['ok' => false, 'erro' => 'Time não encontrado (HTTP 404). Confira bundle_team_id.'], 502);
    if ($cod !== 200) responder(['ok' => false, 'erro' => 'A API de publicação respondeu HTTP ' . $cod . ($erro ? ' (' . $erro . ')' : '') . '.'], 502);
    $contas = [];
    foreach (($j['socialAccounts'] ?? []) as $c) {
        if (!empty($c['deletedAt'])) continue;
        $contas[] = ['tipo' => $c['type'] ?? '', 'nome' => '@' . ltrim((string) ($c['username'] ?? ($c['displayName'] ?? '')), '@')];
    }
    responder(['ok' => true, 'msg' => 'Conectado ao time “' . ($j['name'] ?? $nomeTime ?: $time) . '” (id ' . $time . ') · ' . count($contas) . ' conta(s) conectada(s).', 'contas' => $contas, 'teamId' => $time]);
}

if ($acao === 'conectar') {
    $d = corpoJson();
    $voltar = (string) ($d['voltar'] ?? '');
    if (!preg_match('#^https://#', $voltar)) $voltar = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'connectagendapro.com') . '/?instagram=voltou';
    // 1º: portal de conexão (em português, com a marca do fornecedor escondida quando o plano permite)
    [$cod, $j] = http('POST', $base . '/social-account/create-portal-link', $cabJson, json_encode([
        'teamId' => $time, 'socialAccountTypes' => ['INSTAGRAM'], 'redirectUrl' => $voltar, 'language' => 'pt',
        'instagramConnectionMethod' => 'INSTAGRAM', 'hidePoweredBy' => true, 'expiresIn' => 60]));
    if ($cod < 200 || $cod >= 300 || empty($j['url'])) {
        // 2º: link direto de autorização do Instagram
        [$cod, $j] = http('POST', $base . '/social-account/connect', $cabJson, json_encode([
            'type' => 'INSTAGRAM', 'teamId' => $time, 'redirectUrl' => $voltar, 'instagramConnectionMethod' => 'INSTAGRAM']));
    }
    if ($cod < 200 || $cod >= 300 || empty($j['url'])) {
        responder(['ok' => false, 'erro' => 'Não consegui gerar o link de conexão (HTTP ' . $cod . '). ' . $msgErro($j)], 502);
    }
    responder(['ok' => true, 'url' => $j['url']]);
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
        [$cod, $j] = http('POST', $base . '/upload/from-url', $cabJson, json_encode(['teamId' => $time, 'url' => $imagem]));
    }
    $uploadId = $j['id'] ?? null;
    if ($cod < 200 || $cod >= 300 || !$uploadId) {
        responder(['ok' => false, 'erro' => 'Falha ao enviar a peça (HTTP ' . $cod . '). ' . $msgErro($j)], 502);
    }

    // 2) agenda o post (padrão: daqui a 2 minutos; "agora" = daqui a 30 s, o mínimo que a fila aceita)
    $quando = !empty($d['agora']) ? time() + 30 : (!empty($d['quando']) ? strtotime((string) $d['quando']) : time() + 120);
    $post = json_encode([
        'teamId' => $time,
        'title' => mb_substr($legenda !== '' ? $legenda : 'Publicação da plataforma', 0, 80),
        'postDate' => gmdate('Y-m-d\TH:i:s.000\Z', $quando),
        'status' => 'SCHEDULED',
        'socialAccountTypes' => ['INSTAGRAM'],
        'data' => ['INSTAGRAM' => ['type' => 'POST', 'text' => $legenda, 'uploadIds' => [$uploadId]]],
    ]);
    [$cod, $j] = http('POST', $base . '/post/', $cabJson, $post);
    if ($cod === 404) [$cod, $j] = http('POST', $base . '/posts', $cabJson, $post);   // a documentação cita os dois caminhos
    if ($cod < 200 || $cod >= 300) {
        responder(['ok' => false, 'erro' => 'A API recusou a publicação (HTTP ' . $cod . '). ' . $msgErro($j)], 502);
    }
    responder(['ok' => true, 'msg' => 'Publicação agendada para ' . date('d/m/Y H:i', $quando) . ' (horário do servidor).', 'id' => $j['id'] ?? null]);
}

if ($acao === 'situacao') {
    $id = (string) ($_GET['id'] ?? (corpoJson()['id'] ?? ''));
    if ($id === '') responder(['ok' => false, 'erro' => 'Falta o id do post.'], 400);
    [$cod, $j] = http('GET', $base . '/post/' . rawurlencode($id), $cab);
    if ($cod !== 200) responder(['ok' => false, 'erro' => 'Não consegui consultar o post (HTTP ' . $cod . ').'], 502);
    $st = (string) ($j['status'] ?? '');
    $link = '';
    foreach ((array) ($j['externalData'] ?? []) as $plat => $ext) { if (is_array($ext) && !empty($ext['permalink'])) { $link = $ext['permalink']; break; } }
    $erro = '';
    foreach ((array) ($j['errorsVerbose'] ?? []) as $plat => $e) { if (is_array($e)) { $erro = $e['userFacingMessage'] ?? ($e['errorMessage'] ?? ''); if ($erro) break; } }
    if ($erro === '') foreach ((array) ($j['errors'] ?? []) as $plat => $e) { if (is_string($e) && $e !== '') { $erro = $e; break; } }
    $rotulo = ['SCHEDULED' => 'na fila', 'PROCESSING' => 'publicando', 'RETRYING' => 'tentando de novo', 'POSTED' => 'publicado', 'ERROR' => 'erro', 'REVIEW' => 'em revisão', 'DRAFT' => 'rascunho', 'DELETED' => 'apagado'];
    responder(['ok' => true, 'status' => $st, 'rotulo' => $rotulo[$st] ?? strtolower($st), 'link' => $link, 'erro' => $erro, 'postDate' => $j['postDate'] ?? null]);
}

responder(['ok' => false, 'erro' => 'Ação desconhecida.'], 400);
