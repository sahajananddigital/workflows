<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

// 1. Simple .env file loader
function loadEnv(string $path): array {
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$k, $v] = explode('=', $line, 2) + [1 => ''];
        $env[trim($k)] = trim($v);
    }
    return $env;
}

$env = loadEnv(__DIR__ . '/.env') ?: loadEnv(__DIR__ . '/example.env');

// 2. HTTP Method & Token Authorization
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use POST.']);
    exit;
}

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$expectedToken = $env['AUTH_TOKEN'] ?? '';

if (empty($expectedToken) || !hash_equals('Bearer ' . $expectedToken, $authHeader)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid Bearer token']);
    exit;
}

// 3. Payload Parsing
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

$to = $input['to'] ?? null;
$subject = $input['subject'] ?? null;
$html = $input['html'] ?? '';
$text = $input['text'] ?? '';
$fromEmail = $input['from'] ?? ($env['SMTP_USER'] ?? '');
$fromName = $input['from_name'] ?? '';
$attachments = $input['attachments'] ?? [];

if (empty($to) || empty($subject) || (empty($html) && empty($text))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: to, subject, and text/html']);
    exit;
}

// Format recipient lists
$recipients = is_array($to) ? $to : array_map('trim', explode(',', $to));
$ccList = !empty($input['cc']) ? (is_array($input['cc']) ? $input['cc'] : array_map('trim', explode(',', $input['cc']))) : [];
$bccList = !empty($input['bcc']) ? (is_array($input['bcc']) ? $input['bcc'] : array_map('trim', explode(',', $input['bcc']))) : [];

// 4. Standalone Native SMTP Engine
class NativeSmtpMailer {
    private $socket;

    private function readResponse(): string {
        $data = '';
        while (!feof($this->socket)) {
            $line = fgets($this->socket, 512);
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    }

    private function sendCommand(string $command, array $expectedCodes): string {
        fwrite($this->socket, $command . "\r\n");
        $response = $this->readResponse();
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new Exception("SMTP Command failed: '$command' -> Server Response: $response");
        }
        return $response;
    }

    public function send(array $cfg, string $from, array $allRecipients, string $rawMessage): void {
        $host = $cfg['SMTP_HOST'];
        $port = (int)($cfg['SMTP_PORT'] ?? 587);
        $secure = strtolower($cfg['SMTP_SECURE'] ?? 'tls');

        $remoteSocket = ($secure === 'ssl' ? "ssl://{$host}" : "tcp://{$host}") . ":{$port}";
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ]
        ]);

        $this->socket = @stream_socket_client($remoteSocket, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
        if (!$this->socket) {
            throw new Exception("Connection failed: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, 20);
        $this->readResponse(); // Read initial server banner

        $clientHost = gethostname() ?: 'localhost';
        $this->sendCommand("EHLO $clientHost", [250]);

        // Upgrade connection to TLS for port 587 / STARTTLS
        if ($secure === 'tls') {
            $this->sendCommand("STARTTLS", [220]);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new Exception("STARTTLS cryptographic handshake failed.");
            }
            $this->sendCommand("EHLO $clientHost", [250]);
        }

        // Authenticate
        $this->sendCommand("AUTH LOGIN", [334]);
        $this->sendCommand(base64_encode($cfg['SMTP_USER']), [334]);
        $this->sendCommand(base64_encode($cfg['SMTP_PASS']), [235]);

        // Envelope From & Recipients
        $this->sendCommand("MAIL FROM:<$from>", [250]);
        foreach ($allRecipients as $rcpt) {
            $this->sendCommand("RCPT TO:<$rcpt>", [250, 251]);
        }

        // Data Transmission
        $this->sendCommand("DATA", [354]);
        fwrite($this->socket, $rawMessage . "\r\n.\r\n");
        $dataResp = $this->readResponse();
        if ((int)substr($dataResp, 0, 3) !== 250) {
            throw new Exception("Message transmission rejected: $dataResp");
        }

        $this->sendCommand("QUIT", [221]);
        fclose($this->socket);
    }
}

// 5. Construct MIME Multipart Message
$boundaryMixed = '==_Mixed_' . md5((string)microtime(true));
$boundaryAlt   = '==_Alt_' . md5((string)microtime(true));

$msgId = '<' . time() . '.' . bin2hex(random_bytes(8)) . '@' . ($env['SMTP_HOST'] ?? 'relay') . '>';
$fromDisplay = !empty($fromName) ? "=?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>" : "<$fromEmail>";

$headersRaw  = "Date: " . date('r') . "\r\n";
$headersRaw .= "To: " . implode(', ', $recipients) . "\r\n";
$headersRaw .= "From: $fromDisplay\r\n";
$headersRaw .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
$headersRaw .= "Message-ID: $msgId\r\n";
$headersRaw .= "MIME-Version: 1.0\r\n";

if (!empty($ccList)) {
    $headersRaw .= "Cc: " . implode(', ', $ccList) . "\r\n";
}

$bodyContent = "";
if (!empty($attachments)) {
    $headersRaw .= "Content-Type: multipart/mixed; boundary=\"$boundaryMixed\"\r\n\r\n";
    $bodyContent .= "--$boundaryMixed\r\n";
}

// Alternative multipart (HTML + Plain text fallback)
if (!empty($html) && !empty($text)) {
    $bodyContent .= "Content-Type: multipart/alternative; boundary=\"$boundaryAlt\"\r\n\r\n";
    $bodyContent .= "--$boundaryAlt\r\n";
    $bodyContent .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $bodyContent .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $bodyContent .= chunk_split(base64_encode($text)) . "\r\n";

    $bodyContent .= "--$boundaryAlt\r\n";
    $bodyContent .= "Content-Type: text/html; charset=UTF-8\r\n";
    $bodyContent .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $bodyContent .= chunk_split(base64_encode($html)) . "\r\n";
    $bodyContent .= "--$boundaryAlt--\r\n";
} else {
    $isHtml = !empty($html);
    $bodyContent .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
    $bodyContent .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $bodyContent .= chunk_split(base64_encode($isHtml ? $html : $text)) . "\r\n";
}

// Append Attachments
if (!empty($attachments)) {
    foreach ($attachments as $att) {
        if (!empty($att['filename']) && !empty($att['base64'])) {
            $fileName = $att['filename'];
            $mimeType = $att['mime_type'] ?? 'application/octet-stream';
            $b64Data  = preg_replace('/\s+/', '', $att['base64']);

            $bodyContent .= "\r\n--$boundaryMixed\r\n";
            $bodyContent .= "Content-Type: $mimeType; name=\"$fileName\"\r\n";
            $bodyContent .= "Content-Transfer-Encoding: base64\r\n";
            $bodyContent .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
            $bodyContent .= chunk_split($b64Data) . "\r\n";
        }
    }
    $bodyContent .= "--$boundaryMixed--";
}

$rawMime = $headersRaw . (!empty($attachments) ? "" : "\r\n") . $bodyContent;

// 6. Execute Dispatch
try {
    $mailer = new NativeSmtpMailer();
    $allTargets = array_unique(array_merge($recipients, $ccList, $bccList));
    $mailer->send($env, $fromEmail, $allTargets, $rawMime);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'messageId' => $msgId,
        'message' => 'Email sent directly via native SMTP socket'
    ]);
} catch (Exception $e) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'SMTP relay failed',
        'details' => $e->getMessage()
    ]);
}