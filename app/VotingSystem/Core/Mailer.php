<?php

namespace App\VotingSystem\Core;

class Mailer
{
    public function sendAdminCode(string $to, string $name, string $code, int $expiresMinutes = 10): bool
    {
        $appName = (string) voting_config('app_name', 'Supreme Student Council');
        $subject = $appName . ' admin sign-in code';
        $text = "Hello {$name},\n\n"
            . "Your admin sign-in code is: {$code}\n\n"
            . "This code expires in {$expiresMinutes} minutes. If you did not request this, ignore this email and review admin access immediately.\n\n"
            . "{$appName}";
        $html = $this->template(
            'Administrator Sign-In Code',
            'Administrator Access',
            'Use this verification code to complete your admin sign-in.',
            '<div style="font-size:34px;letter-spacing:10px;font-weight:900;color:#1f1e1b;background:#fff7dc;border:1px solid #f49322;border-radius:14px;padding:18px 20px;text-align:center;">'
            . e($code)
            . '</div>'
            . '<p style="margin:18px 0 0;color:#4f564c;font-size:14px;line-height:1.6;">This code expires in <strong>' . e($expiresMinutes) . ' minutes</strong>. If you did not request this sign-in, ignore this email and review admin access immediately.</p>'
        );

        return $this->sendEmail($to, $subject, $text, $html);
    }

    public function sendVoteReceipt(
        string $to,
        array $voter,
        array $election,
        string $reference,
        string $submittedAt,
        array $chain = []
    ): bool
    {
        $appName = (string) voting_config('app_name', 'Supreme Student Council');
        $subject = $appName . ' official ballot receipt';
        $voterName = (string) ($voter['full_name'] ?? 'Voter');
        $electionTitle = (string) ($election['title'] ?? 'Supreme Student Council Election');
        $submittedDisplay = date('F j, Y g:i A', strtotime($submittedAt) ?: time());

        $blockHash = trim((string) ($chain['block_hash'] ?? ''));
        $previousHash = trim((string) ($chain['previous_hash'] ?? ''));
        $ballotRoot = trim((string) ($chain['ballot_root'] ?? ''));
        $nodesConfirmed = (int) ($chain['nodes_confirmed'] ?? 0);

        $text = "Hello {$voterName},\n\n"
            . "Your official ballot for {$electionTitle} has been received.\n\n"
            . "Receipt reference: {$reference}\n"
            . "Submitted: {$submittedDisplay}\n";

        if ($blockHash !== '') {
            $text .= "\nBlockchain integrity seal\n"
                . "Block hash: {$blockHash}\n";
            if ($previousHash !== '') {
                $text .= "Previous hash: {$previousHash}\n";
            }
            if ($ballotRoot !== '') {
                $text .= "Ballot root: {$ballotRoot}\n";
            }
            if ($nodesConfirmed > 0) {
                $text .= "Nodes confirmed: {$nodesConfirmed}/3\n";
            }
        }

        $text .= "\nThis receipt confirms that your ballot was successfully submitted and your voter record was marked as voted. It does not disclose your candidate selections.\n\n"
            . "{$appName}";

        $rows = $this->detailRow('Reference Code', $reference, true)
            . $this->detailRow('Election', $electionTitle)
            . $this->detailRow('Voter', $voterName)
            . $this->detailRow('Submitted', $submittedDisplay);

        if ($blockHash !== '') {
            $rows .= $this->detailRow('Block Hash', $blockHash, false, true);
            if ($previousHash !== '') {
                $rows .= $this->detailRow('Previous Hash', $previousHash, false, true);
            }
            if ($ballotRoot !== '') {
                $rows .= $this->detailRow('Ballot Root', $ballotRoot, false, true);
            }
            if ($nodesConfirmed > 0) {
                $rows .= $this->detailRow('Nodes Confirmed', $nodesConfirmed . ' / 3');
            }
        }

        $chainNote = $blockHash !== ''
            ? '<p style="margin:16px 0 0;color:#4f564c;font-size:14px;line-height:1.6;">Your ballot was sealed to the local 3-node hash chain. Keep this receipt so you can verify the block hash later. For privacy, your selected candidates are not included in this email.</p>'
            : '<p style="margin:16px 0 0;color:#4f564c;font-size:14px;line-height:1.6;">This receipt confirms successful ballot submission only. For privacy, your selected candidates are not included in this email.</p>';

        $html = $this->template(
            'Official Ballot Receipt',
            'Vote Submitted',
            'Your official ballot has been received and recorded by the Supreme Student Council election system.',
            '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0 10px;margin-top:6px;">'
            . $rows
            . '</table>'
            . $chainNote
        );

        return $this->sendEmail($to, $subject, $text, $html);
    }

    public function sendVoterCode(string $to, array $voter, string $code, int $expiresMinutes = 10): bool
    {
        $appName = (string) voting_config('app_name', 'Supreme Student Council');
        $voterName = (string) ($voter['full_name'] ?? 'Voter');
        $subject = $appName . ' ballot verification code';
        $text = "Hello {$voterName},\n\n"
            . "Your ballot verification code is: {$code}\n\n"
            . "Enter this code before submitting your official ballot. This code expires in {$expiresMinutes} minutes. If you did not request this, return to the voting system and do not submit a ballot.\n\n"
            . "{$appName}";
        $html = $this->template(
            'Ballot Verification Code',
            'Official Ballot',
            'Enter this verification code before submitting your official ballot.',
            '<div style="font-size:34px;letter-spacing:10px;font-weight:900;color:#1f1e1b;background:#fff7dc;border:1px solid #f49322;border-radius:14px;padding:18px 20px;text-align:center;">'
            . e($code)
            . '</div>'
            . '<p style="margin:18px 0 0;color:#4f564c;font-size:14px;line-height:1.6;">This code expires in <strong>' . e($expiresMinutes) . ' minutes</strong>. Keep it private and use it only to submit your own official ballot.</p>'
        );

        return $this->sendEmail($to, $subject, $text, $html);
    }

    public function sendStaffCredentials(string $to, string $name, string $password, string $role, string $loginUrl): bool
    {
        $appName = (string) voting_config('app_name', 'Supreme Student Council');
        $displayName = trim($name) !== '' ? trim($name) : 'Canvassing Staff';
        $roleLabel = $role === 'view_only' ? 'View Only' : 'Canvassing';
        $subject = $appName . ' staff account credentials';
        $text = "Hello {$displayName},\n\n"
            . "A {$roleLabel} staff account has been created for the {$appName} voting system.\n\n"
            . "Login link: {$loginUrl}\n"
            . "Email: {$to}\n"
            . "Temporary password: {$password}\n\n"
            . "Privacy and security reminder: this account is assigned only to you. Do not share this email, password, or account access with anyone. If you did not expect this account, contact the Campus Electoral Board immediately.\n\n"
            . "{$appName}";

        $html = $this->template(
            'Staff Account Credentials',
            'Staff Access',
            'Your canvassing staff account has been created. Use the credentials below to sign in.',
            '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0 10px;margin-top:6px;">'
            . $this->detailRow('Login Link', $loginUrl)
            . $this->detailRow('Email', $to)
            . $this->detailRow('Password', $password, true)
            . $this->detailRow('Role', $roleLabel)
            . '</table>'
            . '<div style="margin:18px 0 0;background:#fff7dc;border:1px solid rgba(244,147,34,0.45);border-radius:14px;padding:14px 16px;color:#4f564c;font-size:14px;line-height:1.6;">'
            . '<strong style="color:#1f1e1b;">Privacy reminder:</strong> This account is assigned only to you. Do not share this email, password, or account access with anyone. If you did not expect this account, contact the Campus Electoral Board immediately.'
            . '</div>'
        );

        return $this->sendEmail($to, $subject, $text, $html);
    }

    public function sendText(string $to, string $subject, string $body): bool
    {
        return $this->sendEmail($to, $subject, $body);
    }

    private function sendEmail(string $to, string $subject, string $textBody, ?string $htmlBody = null): bool
    {
        $mailer = strtolower((string) voting_config('mail.mailer', 'mail'));

        if ($mailer === 'log') {
            $this->writeLog($to, $subject, $textBody, $htmlBody);
            return true;
        }

        if ($mailer === 'smtp') {
            return $this->sendSmtp($to, $subject, $textBody, $htmlBody);
        }

        return $this->sendMail($to, $subject, $textBody, $htmlBody);
    }

    private function sendMail(string $to, string $subject, string $textBody, ?string $htmlBody): bool
    {
        $fromAddress = (string) voting_config('mail.from_address', 'no-reply@localhost');
        $fromName = (string) voting_config('mail.from_name', voting_config('app_name', 'Supreme Student Council'));
        $body = $this->body($textBody, $htmlBody);
        $contentType = $htmlBody === null
            ? 'text/plain; charset=UTF-8'
            : 'multipart/related; boundary="' . $body['boundary'] . '"';
        $headers = [
            'From: ' . $this->formatAddress($fromAddress, $fromName),
            'Reply-To: ' . $this->formatAddress($fromAddress, $fromName),
            'MIME-Version: 1.0',
            'Content-Type: ' . $contentType,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        return mail($to, $subject, $body['content'], implode("\r\n", $headers));
    }

    private function sendSmtp(string $to, string $subject, string $textBody, ?string $htmlBody): bool
    {
        $host = (string) voting_config('mail.host', '');
        $port = (int) voting_config('mail.port', 587);
        $encryption = strtolower((string) voting_config('mail.encryption', 'tls'));
        $username = (string) voting_config('mail.username', '');
        $password = (string) voting_config('mail.password', '');
        $fromAddress = (string) voting_config('mail.from_address', $username ?: 'no-reply@localhost');
        $fromName = (string) voting_config('mail.from_name', voting_config('app_name', 'Supreme Student Council'));

        if ($host === '' || $fromAddress === '') {
            error_log('SMTP mail is not configured.');
            return false;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);

        if (!is_resource($socket)) {
            error_log("SMTP connection failed: {$errno} {$errstr}");
            return false;
        }

        stream_set_timeout($socket, 15);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO ' . $this->smtpDomain(), [250]);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }
                if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                        throw new \RuntimeException('Unable to start SMTP TLS encryption.');
                    }
                }
                $this->command($socket, 'EHLO ' . $this->smtpDomain(), [250]);
            }

            if ($username !== '') {
                $this->command($socket, 'AUTH LOGIN', [334]);
                $this->command($socket, base64_encode($username), [334]);
                $this->command($socket, base64_encode($password), [235]);
            }

            $this->command($socket, 'MAIL FROM:<' . $fromAddress . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            fwrite($socket, $this->message($fromAddress, $fromName, $to, $subject, $textBody, $htmlBody));
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
            fclose($socket);

            return true;
        } catch (\Throwable $exception) {
            error_log('SMTP send failed: ' . $exception->getMessage());
            if (is_resource($socket)) {
                fclose($socket);
            }

            throw $exception;
        }
    }

    private function message(string $fromAddress, string $fromName, string $to, string $subject, string $textBody, ?string $htmlBody): string
    {
        $body = $this->body($textBody, $htmlBody);
        $contentType = $htmlBody === null
            ? 'text/plain; charset=UTF-8'
            : 'multipart/related; boundary="' . $body['boundary'] . '"';
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->formatAddress($fromAddress, $fromName),
            'To: <' . $to . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: ' . $contentType,
            'Auto-Submitted: auto-generated',
        ];

        if ($htmlBody === null) {
            $headers[] = 'Content-Transfer-Encoding: 8bit';
        }

        $message = implode("\n", $headers) . "\n\n" . $body['content'];
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = preg_replace('/^\./m', '..', $message);

        return str_replace("\n", "\r\n", $message) . "\r\n.\r\n";
    }

    private function body(string $textBody, ?string $htmlBody): array
    {
        if ($htmlBody === null) {
            return [
                'boundary' => '',
                'content' => str_replace(["\r\n", "\r"], "\n", $textBody),
            ];
        }

        $relatedBoundary = 'rel_' . bin2hex(random_bytes(12));
        $alternativeBoundary = 'alt_' . bin2hex(random_bytes(12));
        $logo = $this->inlineLogo();
        $content = '';
        $content .= '--' . $relatedBoundary . "\n";
        $content .= 'Content-Type: multipart/alternative; boundary="' . $alternativeBoundary . '"' . "\n\n";
        $content .= '--' . $alternativeBoundary . "\n";
        $content .= "Content-Type: text/plain; charset=UTF-8\n";
        $content .= "Content-Transfer-Encoding: 8bit\n\n";
        $content .= str_replace(["\r\n", "\r"], "\n", $textBody) . "\n\n";
        $content .= '--' . $alternativeBoundary . "\n";
        $content .= "Content-Type: text/html; charset=UTF-8\n";
        $content .= "Content-Transfer-Encoding: 8bit\n\n";
        $content .= str_replace(["\r\n", "\r"], "\n", $htmlBody) . "\n\n";
        $content .= '--' . $alternativeBoundary . "--\n";

        if ($logo !== null) {
            $logoContent = file_get_contents($logo['path']);

            if ($logoContent === false) {
                $logo = null;
            }
        }

        if ($logo !== null) {
            $content .= '--' . $relatedBoundary . "\n";
            $content .= 'Content-Type: ' . $logo['mime'] . '; name="' . $logo['name'] . '"' . "\n";
            $content .= "Content-Transfer-Encoding: base64\n";
            $content .= 'Content-ID: <' . $logo['cid'] . '>' . "\n";
            $content .= 'Content-Disposition: inline; filename="' . $logo['name'] . '"' . "\n\n";
            $content .= chunk_split(base64_encode($logoContent)) . "\n";
        }

        $content .= '--' . $relatedBoundary . '--';

        return [
            'boundary' => $relatedBoundary,
            'content' => $content,
        ];
    }

    private function template(string $title, string $eyebrow, string $intro, string $content): string
    {
        $appName = e((string) voting_config('app_name', 'Supreme Student Council'));
        $logo = $this->inlineLogo();
        $logoSrc = $logo !== null ? 'cid:' . $logo['cid'] : '';
        $logoHtml = $logoSrc !== ''
            ? '<img src="' . e($logoSrc) . '" width="72" height="72" alt="OrgChain Logo" style="display:block;width:72px;height:72px;border-radius:50%;object-fit:cover;">'
            : '<div style="width:72px;height:72px;border-radius:50%;background:#f49322;color:#1f1e1b;font-weight:900;display:flex;align-items:center;justify-content:center;">SSC</div>';

        return '<!doctype html>'
            . '<html><body style="margin:0;padding:0;background:#f7ebd7;font-family:Inter,Arial,sans-serif;color:#2f312a;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#f7ebd7;padding:28px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;max-width:640px;background:#ffffff;border:1px solid rgba(111,115,95,0.22);border-radius:18px;overflow:hidden;">'
            . '<tr><td style="background:linear-gradient(135deg,#6bb2b3 0%,#9ca18e 48%,#f49322 100%);padding:26px 28px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;"><tr>'
            . '<td style="width:86px;vertical-align:middle;">' . $logoHtml . '</td>'
            . '<td style="vertical-align:middle;">'
            . '<div style="font-size:12px;letter-spacing:1.2px;text-transform:uppercase;font-weight:900;color:rgba(31,30,27,0.68);white-space:nowrap;">'
            . 'Campus&nbsp;Electoral&nbsp;Board</div>'
            . '<div style="font-size:22px;line-height:1.2;font-weight:900;color:#1f1e1b;">' . $appName . '</div>'
            . '</td></tr></table>'
            . '</td></tr>'
            . '<tr><td style="padding:30px 28px 10px;">'
            . '<div style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;font-weight:900;color:#6f735f;margin-bottom:8px;">' . e($eyebrow) . '</div>'
            . '<h1 style="font-family:Arial,sans-serif;font-size:28px;line-height:1.15;margin:0 0 12px;color:#4f564c;">' . e($title) . '</h1>'
            . '<p style="margin:0 0 22px;color:#3f6665;font-size:15px;line-height:1.6;font-weight:650;">' . e($intro) . '</p>'
            . $content
            . '</td></tr>'
            . '<tr><td style="padding:18px 28px 28px;">'
            . '<div style="border-top:1px solid rgba(111,115,95,0.18);padding-top:16px;color:#6f735f;font-size:12px;line-height:1.55;">'
            . 'This is an official automated message from the Supreme Student Council voting system. Please keep this email for your records.'
            . '</div></td></tr>'
            . '</table>'
            . '</td></tr></table>'
            . '</body></html>';
    }

    private function detailRow(string $label, string $value, bool $highlight = false, bool $mono = false): string
    {
        $valueStyle = $highlight
            ? 'font-size:18px;letter-spacing:1px;font-weight:900;color:#1f1e1b;'
            : 'font-size:14px;font-weight:800;color:#1f1e1b;';

        if ($mono) {
            $valueStyle = 'font-size:12px;font-weight:700;color:#1f1e1b;font-family:Consolas,Monaco,monospace;word-break:break-all;line-height:1.45;';
        }

        return '<tr>'
            . '<td style="width:150px;background:#f7ebd7;border-radius:10px 0 0 10px;padding:12px 14px;color:#6f735f;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:0.8px;vertical-align:top;">' . e($label) . '</td>'
            . '<td style="background:#fffbea;border-radius:0 10px 10px 0;padding:12px 14px;' . $valueStyle . '">' . e($value) . '</td>'
            . '</tr>';
    }

    private function inlineLogo(): ?array
    {
        $path = voting_public_assets_path('img/ssc-favicon.png');

        if (!is_file($path)) {
            $path = voting_public_assets_path('img/ssc-logo-circle.png');
        }

        if (!is_file($path)) {
            return null;
        }

        return [
            'cid' => 'ssc-logo',
            'path' => $path,
            'mime' => 'image/png',
            'name' => 'ssc-logo.png',
        ];
    }

    private function command(mixed $socket, string $command, array $expected): string
    {
        fwrite($socket, $command . "\r\n");

        return $this->expect($socket, $expected);
    }

    private function expect(mixed $socket, array $expected): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^\d{3}\s/', $line)) {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);

        if (!in_array($code, $expected, true)) {
            throw new \RuntimeException(trim($response) ?: 'Empty SMTP response.');
        }

        return $response;
    }

    private function formatAddress(string $email, string $name): string
    {
        $safeName = trim(str_replace(['"', "\r", "\n"], '', $name));

        return $safeName === '' ? '<' . $email . '>' : '"' . $safeName . '" <' . $email . '>';
    }

    private function smtpDomain(): string
    {
        $host = parse_url((string) voting_config('app_url', ''), PHP_URL_HOST);

        return $host ?: 'localhost';
    }

    private function writeLog(string $to, string $subject, string $body, ?string $htmlBody = null): void
    {
        $path = project_path((string) voting_config('mail.log_path', 'storage/logs/mail.log'));

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents(
            $path,
            '[' . date('Y-m-d H:i:s') . "] To: {$to} | Subject: {$subject}\n{$body}"
            . ($htmlBody !== null ? "\n\nHTML:\n{$htmlBody}" : '')
            . "\n\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
