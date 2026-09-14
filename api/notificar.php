<?php
/* =======================================================================
   Avisos por e-mail e WhatsApp (comunicados, cobrança de leitura, prazos).

   E-mail:   pelo servidor da hospedagem (função mail() da Hostinger), com o
             remetente configurado. Para volume grande, trocar por Amazon SES.
   WhatsApp: API oficial do WhatsApp Cloud (Meta). Fora da janela de 24 h a
             Meta só aceita MODELO aprovado (template); o texto livre serve
             para teste com um número que falou com a empresa nas últimas 24 h.

   GET  ?acao=ping      → servidor configurado? (sem chave)
   GET  ?acao=status    → o que está configurado          (chave)
   POST ?acao=email     → {para, assunto, texto}          (chave)
   POST ?acao=whatsapp  → {para (5511...), texto | modelo, idioma?}  (chave)
   ======================================================================= */
require __DIR__ . '/_config.php';

$acao = $_GET['acao'] ?? 'ping';
if ($acao === 'ping') {
    // dados públicos da instalação: o número do WhatsApp do suporte (aparece no Help Desk de todos os navegadores)
    responder(['ok' => true, 'configurado' => is_array($CFG), 'servico' => 'avisos', 'whatsSuporte' => (string) cfg('whatsapp_suporte')]);
}
/* chamado aberto na plataforma → e-mail (e WhatsApp, se configurado) para o suporte. Sem chave: destinatário fixo, só do próprio site. */
if ($acao === 'chamado') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !mesmoSite()) responder(['ok' => false, 'erro' => 'Só pelo próprio site.'], 403);
    if (!is_array($CFG)) responder(['ok' => false, 'erro' => 'Servidor sem configuração.'], 503);
    $d = corpoJson();
    $para = cfg('email_suporte', cfg('smtp_user', cfg('email_remetente')));
    $canais = [];
    $lim = function ($k, $n) use ($d) { return mb_substr(trim((string) ($d[$k] ?? '')), 0, $n); };
    $id = $lim('id', 12); $assunto = $lim('assunto', 120);
    $texto = "Novo chamado " . $id . " na plataforma " . cfg('plataforma', 'Prontos em Rede') . "\n\n" .
             "Assunto: " . $assunto . "\n" . "Quem abriu: " . $lim('autor', 80) . " (" . $lim('papel', 60) . ")\n" .
             "Unidade: " . $lim('unidade', 80) . "\n" . "Prioridade: " . $lim('prio', 20) . " · SLA " . $lim('sla', 20) . " · canal " . $lim('canal', 30) . "\n" .
             ($lim('descricao', 2000) !== '' ? "\nDescrição:\n" . $lim('descricao', 2000) . "\n" : '') .
             "\nAbrir na plataforma: " . $lim('link', 200) . "\n";
    if ($para !== '' && filter_var($para, FILTER_VALIDATE_EMAIL)) {
        $ok = false;
        if (cfg('smtp_user') !== '') {
            [$ok, $det] = smtpEnviar(cfg('smtp_host', 'smtp.gmail.com'), (int) cfg('smtp_port', 465), cfg('smtp_user'), cfg('smtp_pass'),
                                     cfg('email_remetente', cfg('smtp_user')), cfg('email_nome', 'Prontos em Rede'), $para, 'Chamado ' . $id . ' · ' . $assunto, $texto);
        } else {
            $de = cfg('email_remetente');
            if ($de !== '') $ok = @mail($para, '=?UTF-8?B?' . base64_encode('Chamado ' . $id . ' · ' . $assunto) . '?=', $texto,
                "From: =?UTF-8?B?" . base64_encode(cfg('email_nome', 'Prontos em Rede')) . "?= <" . $de . ">\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n", '-f' . $de);
        }
        if ($ok) $canais[] = 'e-mail';
    }
    $zap = preg_replace('/\D/', '', (string) cfg('whatsapp_suporte'));
    if ($zap !== '' && cfg('whatsapp_token') !== '' && cfg('whatsapp_phone_id') !== '') {
        if (strlen($zap) === 10 || strlen($zap) === 11) $zap = '55' . $zap;
        [$cod] = http('POST', 'https://graph.facebook.com/v21.0/' . rawurlencode(cfg('whatsapp_phone_id')) . '/messages',
                      ['Authorization: Bearer ' . cfg('whatsapp_token'), 'Content-Type: application/json'],
                      json_encode(['messaging_product' => 'whatsapp', 'to' => $zap, 'type' => 'text', 'text' => ['body' => mb_substr($texto, 0, 1000)]]));
        if ($cod >= 200 && $cod < 300) $canais[] = 'WhatsApp';
    }
    responder($canais ? ['ok' => true, 'canais' => implode(' e ', $canais)] : ['ok' => false, 'erro' => 'Nenhum canal de aviso configurado (email_suporte / smtp ou WhatsApp).']);
}

exigirChave();

/* envio por SMTP — TLS direto (465) ou STARTTLS (587), sem biblioteca externa */
function smtpTentar($host, $porta, $usuario, $senha, $de, $nome, $para, $assunto, $texto) {
    $tls = ((int) $porta === 465);
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $s = @stream_socket_client(($tls ? 'ssl://' : 'tcp://') . $host . ':' . $porta, $en, $es, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$s) return [false, 'porta ' . $porta . ': não conectou (' . ($es ?: 'erro ' . $en) . ')'];
    stream_set_timeout($s, 20);
    $ler = function () use ($s) { $r = ''; while (($l = fgets($s, 1024)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] !== '-') break; } return $r; };
    $cmd = function ($c, $ok) use ($s, $ler) { fwrite($s, $c . "\r\n"); $r = $ler(); return [substr($r, 0, 1) === (string) $ok, trim($r)]; };
    $banner = trim($ler());
    if ($banner === '') { fclose($s); return [false, 'porta ' . $porta . ': conectou, mas o servidor não respondeu (saída SMTP bloqueada pela hospedagem?)']; }
    [$ok, $r] = $cmd('EHLO connectagendapro.com', 2); if (!$ok) { fclose($s); return [false, 'EHLO → ' . $r]; }
    if (!$tls) {
        [$ok, $r] = $cmd('STARTTLS', 2); if (!$ok) { fclose($s); return [false, 'STARTTLS → ' . $r]; }
        if (!@stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($s); return [false, 'porta ' . $porta . ': falha ao ligar o TLS']; }
        [$ok, $r] = $cmd('EHLO connectagendapro.com', 2); if (!$ok) { fclose($s); return [false, 'EHLO (TLS) → ' . $r]; }
    }
    foreach ([['AUTH LOGIN', 3, 'AUTH'], [base64_encode($usuario), 3, 'usuário'], [base64_encode($senha), 2, 'senha'],
              ['MAIL FROM:<' . $de . '>', 2, 'MAIL FROM'], ['RCPT TO:<' . $para . '>', 2, 'RCPT TO'], ['DATA', 3, 'DATA']] as $par) {
        [$ok, $r] = $cmd($par[0], $par[1]);
        if (!$ok) { fclose($s); return [false, $par[2] . ' → ' . ($r ?: 'sem resposta')]; }
    }
    $msg = "From: =?UTF-8?B?" . base64_encode($nome) . "?= <" . $de . ">\r\nTo: <" . $para . ">\r\n" .
           "Subject: =?UTF-8?B?" . base64_encode($assunto) . "?=\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n" .
           "Date: " . date(DATE_RFC2822) . "\r\n\r\n" . str_replace("\n.", "\n..", $texto) . "\r\n.";
    [$ok, $r] = $cmd($msg, 2);
    $cmd('QUIT', 2); fclose($s);
    return [$ok, $ok ? $r : 'envio → ' . $r];
}
function smtpEnviar($host, $porta, $usuario, $senha, $de, $nome, $para, $assunto, $texto) {
    $erros = [];
    foreach (array_unique([587, (int) $porta, 465]) as $pt) {          // 587 primeiro: é a que a hospedagem deixa passar
        [$ok, $det] = smtpTentar($host, $pt, $usuario, $senha, $de, $nome, $para, $assunto, $texto);
        if ($ok) return [true, $det];
        $erros[] = $det;
        if (strpos($det, 'senha') === 0 || strpos($det, 'usuário') === 0) break;   // credencial errada: não adianta trocar de porta
    }
    return [false, implode(' | ', $erros)];
}

if ($acao === 'status') {
    $temMail = cfg('email_remetente') !== '' || cfg('smtp_user') !== '';
    $temZap  = cfg('whatsapp_token') !== '' && cfg('whatsapp_phone_id') !== '';
    responder(['ok' => $temMail || $temZap,
               'msg' => 'E-mail: ' . ($temMail ? 'configurado (' . cfg('email_remetente', cfg('smtp_user')) . (cfg('smtp_user') !== '' ? ', via SMTP' : ', via servidor') . ')' : 'falta email_remetente') .
                        ' · WhatsApp: ' . ($temZap ? 'configurado' : 'falta whatsapp_token e whatsapp_phone_id') . '.']);
}

if ($acao === 'email') {
    $d = corpoJson();
    $para = trim((string) ($d['para'] ?? ''));
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) responder(['ok' => false, 'erro' => 'E-mail do destinatário inválido.'], 400);
    $de = cfg('email_remetente', cfg('smtp_user'));
    if ($de === '') responder(['ok' => false, 'configurado' => false, 'erro' => 'Falta email_remetente (ou smtp_user) no prontos-config.php.'], 503);
    $nome = cfg('email_nome', 'Prontos em Rede');
    if (cfg('smtp_user') !== '') {
        $assuntoTxt = mb_substr((string) ($d['assunto'] ?? 'Aviso da plataforma'), 0, 150);
        [$ok, $det] = smtpEnviar(cfg('smtp_host', 'smtp.gmail.com'), (int) cfg('smtp_port', 465), cfg('smtp_user'), cfg('smtp_pass'),
                                 $de, $nome, $para, $assuntoTxt, mb_substr((string) ($d['texto'] ?? ''), 0, 5000));
        responder($ok ? ['ok' => true, 'msg' => 'E-mail enviado por ' . $de . ' para ' . $para . '.']
                      : ['ok' => false, 'erro' => 'O servidor de e-mail recusou: ' . $det], $ok ? 200 : 502);
    }
    $assunto = '=?UTF-8?B?' . base64_encode(mb_substr((string) ($d['assunto'] ?? 'Aviso da plataforma'), 0, 150)) . '?=';
    $texto = mb_substr((string) ($d['texto'] ?? ''), 0, 5000);
    $cab = "From: =?UTF-8?B?" . base64_encode($nome) . "?= <" . $de . ">\r\n" .
           "Reply-To: " . $de . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $ok = @mail($para, $assunto, $texto, $cab, '-f' . $de);
    responder($ok ? ['ok' => true, 'msg' => 'E-mail entregue ao servidor para ' . $para . '.']
                  : ['ok' => false, 'erro' => 'O servidor recusou o envio. Confira se o remetente é uma conta de e-mail do domínio.'], $ok ? 200 : 502);
}

if ($acao === 'whatsapp') {
    $d = corpoJson();
    $para = preg_replace('/\D/', '', (string) ($d['para'] ?? ''));
    if (strlen($para) === 10 || strlen($para) === 11) $para = '55' . $para;          // número brasileiro sem o DDI
    if (strlen($para) < 12) responder(['ok' => false, 'erro' => 'Número inválido: use DDD + número (ex.: 15988041775) ou DDI + DDD + número.'], 400);
    $token = cfg('whatsapp_token'); $fone = cfg('whatsapp_phone_id');
    if ($token === '' || $fone === '') responder(['ok' => false, 'configurado' => false, 'erro' => 'Falta whatsapp_token ou whatsapp_phone_id no prontos-config.php.'], 503);
    $msg = ['messaging_product' => 'whatsapp', 'to' => $para];
    if (!empty($d['modelo'])) {
        $msg['type'] = 'template';
        $msg['template'] = ['name' => (string) $d['modelo'], 'language' => ['code' => (string) ($d['idioma'] ?? 'pt_BR')]];
    } else {
        $msg['type'] = 'text';
        $msg['text'] = ['body' => mb_substr((string) ($d['texto'] ?? ''), 0, 4000)];
    }
    [$cod, $j] = http('POST', 'https://graph.facebook.com/v21.0/' . rawurlencode($fone) . '/messages',
                      ['Authorization: Bearer ' . $token, 'Content-Type: application/json'], json_encode($msg));
    $codErro = (int) ($j['error']['code'] ?? 0);
    if (($cod < 200 || $cod >= 300) && $msg['type'] === 'text' && in_array($codErro, [131047, 131026, 100], true)) {
        // fora da janela de 24 h a Meta só aceita modelo aprovado: manda o modelo de teste da própria Meta
        $msg = ['messaging_product' => 'whatsapp', 'to' => $para, 'type' => 'template',
                'template' => ['name' => 'hello_world', 'language' => ['code' => 'en_US']]];
        [$cod, $j] = http('POST', 'https://graph.facebook.com/v21.0/' . rawurlencode($fone) . '/messages',
                          ['Authorization: Bearer ' . $token, 'Content-Type: application/json'], json_encode($msg));
        if ($cod >= 200 && $cod < 300) responder(['ok' => true, 'msg' => 'Enviado o modelo de teste da Meta (hello_world) para +' . $para . '. Texto livre só é aceito nas 24 h seguintes a uma mensagem da pessoa para o número.', 'id' => $j['messages'][0]['id'] ?? null]);
    }
    if ($cod < 200 || $cod >= 300) {
        $detalhe = $j['error']['message'] ?? 'sem detalhe';
        if ($codErro === 131030) $detalhe = 'este número não está na lista de destinatários de teste da Meta (WhatsApp → Configuração da API → Para → Gerenciar lista)';
        if ($codErro === 190) $detalhe = 'token expirado ou inválido: gere outro em Meta for Developers → WhatsApp → Configuração da API';
        responder(['ok' => false, 'erro' => 'A API do WhatsApp recusou (HTTP ' . $cod . '): ' . $detalhe . '.'], 502);
    }
    responder(['ok' => true, 'msg' => 'Mensagem aceita pelo WhatsApp para +' . $para . '.', 'id' => $j['messages'][0]['id'] ?? null]);
}

responder(['ok' => false, 'erro' => 'Ação desconhecida.'], 400);
