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
    responder(['ok' => true, 'configurado' => is_array($CFG), 'servico' => 'avisos']);
}
exigirChave();

if ($acao === 'status') {
    $temMail = cfg('email_remetente') !== '';
    $temZap  = cfg('whatsapp_token') !== '' && cfg('whatsapp_phone_id') !== '';
    responder(['ok' => $temMail || $temZap,
               'msg' => 'E-mail: ' . ($temMail ? 'configurado (' . cfg('email_remetente') . ')' : 'falta email_remetente') .
                        ' · WhatsApp: ' . ($temZap ? 'configurado' : 'falta whatsapp_token e whatsapp_phone_id') . '.']);
}

if ($acao === 'email') {
    $d = corpoJson();
    $para = trim((string) ($d['para'] ?? ''));
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) responder(['ok' => false, 'erro' => 'E-mail do destinatário inválido.'], 400);
    $de = cfg('email_remetente');
    if ($de === '') responder(['ok' => false, 'configurado' => false, 'erro' => 'Falta email_remetente no prontos-config.php.'], 503);
    $nome = cfg('email_nome', 'Prontos em Rede');
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
