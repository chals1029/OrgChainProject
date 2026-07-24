<?php

namespace App\VotingSystem\Controllers;

use App\VotingSystem\Core\Controller;
use App\VotingSystem\Core\GoogleOAuthClient;
use App\VotingSystem\Core\Mailer;
use App\VotingSystem\Core\RateLimiter;
use App\VotingSystem\Models\AuditLog;
use App\VotingSystem\Models\Election;
use App\VotingSystem\Models\Position;
use App\VotingSystem\Models\Vote;
use App\VotingSystem\Models\Voter;
use InvalidArgumentException;
use RuntimeException;

class VoterController extends Controller
{
    private const BALLOT_CODE_EXPIRES_SECONDS = 600;
    private const BALLOT_CODE_RESEND_SECONDS = 60;

    public function verify(): void
    {
        voting_flash('warning', 'Please use Continue with BatStateU Google Workspace Account to access your official ballot.');
        $this->redirect('/');
    }

    public function googleRedirect(): void
    {
        $election = (new Election())->current();

        if (!$election || $election['status'] !== 'open') {
            voting_flash('warning', 'Voting is not open right now.');
            $this->redirect('/');
        }

        $google = new GoogleOAuthClient();

        if (!$google->isConfigured()) {
            voting_flash('error', 'Google sign-in is not configured yet.');
            $this->redirect('/');
        }

        $state = bin2hex(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));

        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['google_oauth_nonce'] = $nonce;
        $_SESSION['google_oauth_started_at'] = time();

        header('Location: ' . $google->authorizationUrl($state, $nonce));
        exit;
    }

    public function googleCallback(): void
    {
        $election = (new Election())->current();

        if (!$election || $election['status'] !== 'open') {
            $this->clearGoogleOAuthState();
            voting_flash('warning', 'Voting is not open right now.');
            $this->redirect('/');
        }

        if (!empty($_GET['error'])) {
            $this->clearGoogleOAuthState();
            voting_flash('error', 'Google sign-in was cancelled or denied.');
            $this->redirect('/');
        }

        $expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
        $state = (string) ($_GET['state'] ?? '');
        $expectedNonce = (string) ($_SESSION['google_oauth_nonce'] ?? '');

        if ($expectedState === '' || $state === '' || !hash_equals($expectedState, $state)) {
            $this->clearGoogleOAuthState();
            voting_flash('error', 'Google sign-in could not be verified. Please try again.');
            $this->redirect('/');
        }

        $code = (string) ($_GET['code'] ?? '');
        $this->clearGoogleOAuthState();

        if ($code === '') {
            voting_flash('error', 'Google did not return a sign-in code. Please try again.');
            $this->redirect('/');
        }

        try {
            $profile = (new GoogleOAuthClient())->userFromCode($code, $expectedNonce);
        } catch (RuntimeException $exception) {
            error_log('Google OAuth failed: ' . $exception->getMessage());
            voting_flash('error', 'Google sign-in failed. Please try again or use manual verification.');
            $this->redirect('/');
        }

        $email = strtolower(trim((string) ($profile['email'] ?? '')));

        if (!($profile['email_verified'] ?? false)) {
            voting_flash('error', 'Your Google email is not verified.');
            $this->redirect('/');
        }

        if (!$this->isAllowedSchoolEmail($email)) {
            voting_flash('error', $this->schoolEmailErrorMessage());
            $this->redirect('/');
        }

        $voter = (new Voter())->findByEmail($email);

        if (!$voter) {
            voting_flash('error', 'We could not match your Google email with the official enlisted voter list.');
            $this->redirect('/');
        }

        if ((int) $voter['has_voted'] === 1) {
            voting_flash('warning', 'This student record has already submitted a vote.');
            $this->redirect('/');
        }

        session_regenerate_id(true);
        $_SESSION['verified_voter_id'] = (int) $voter['id'];
        $_SESSION['verified_voter_login_at'] = time();
        $_SESSION['verified_voter_last_seen'] = time();
        $_SESSION['verified_voter_ua_hash'] = $this->userAgentHash();
        unset($_SESSION['_old'], $_SESSION['pending_voter_ballot_code']);
        $this->redirect('/vote/ballot');
    }

    public function ballot(): void
    {
        $voterId = $this->verifiedVoterId();

        if (!$voterId) {
            voting_flash('warning', 'Please verify your voter information first.');
            $this->redirect('/');
        }

        $voter = (new Voter())->find((int) $voterId);
        $election = (new Election())->current();

        if (!$voter || !$election || $election['status'] !== 'open' || (int) $voter['has_voted'] === 1) {
            unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_ballot_code']);
            voting_flash('warning', 'Your voting session is no longer available.');
            $this->redirect('/');
        }

        $positions = (new Position())->forElection((int) $election['id']);

        $this->view('voter/ballot', [
            'title' => 'Official Ballot',
            'election' => $election,
            'voter' => $voter,
            'positions' => $positions,
        ]);
    }

    public function sendBallotCode(): void
    {
        $this->requirePost();
        $voterId = $this->verifiedVoterId();

        if (!$voterId) {
            $this->jsonResponse(['success' => false, 'message' => 'Your voting session expired. Please sign in again.'], 401);
        }

        $election = (new Election())->current();
        $voter = (new Voter())->find((int) $voterId);

        if (!$voter || !$election || $election['status'] !== 'open' || (int) $voter['has_voted'] === 1) {
            unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_ballot_code']);
            $this->jsonResponse(['success' => false, 'message' => 'Your voting session is no longer available.'], 409);
        }

        $email = trim((string) ($voter['email'] ?? ''));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->jsonResponse(['success' => false, 'message' => 'Your voter email is invalid. Please contact the election administrator.'], 422);
        }

        $identifier = $this->ballotCodeIdentifier((int) $voterId);
        $pending = $_SESSION['pending_voter_ballot_code'] ?? null;

        if ($pending && (int) ($pending['sent_at'] ?? 0) > time() - self::BALLOT_CODE_RESEND_SECONDS) {
            $retryIn = max(1, self::BALLOT_CODE_RESEND_SECONDS - (time() - (int) ($pending['sent_at'] ?? time())));
            $this->jsonResponse([
                'success' => false,
                'message' => 'Please wait a minute before generating another verification number.',
                'retry_in' => $retryIn,
            ], 429);
        }

        if (RateLimiter::tooManyAttempts('voter_ballot_code_send', $identifier, 5, self::BALLOT_CODE_EXPIRES_SECONDS)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Too many verification number requests. Please wait 10 minutes and try again.',
            ], 429);
        }

        $code = (string) random_int(100000, 999999);
        $_SESSION['pending_voter_ballot_code'] = [
            'voter_id' => (int) $voterId,
            'election_id' => (int) $election['id'],
            'email' => $email,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + self::BALLOT_CODE_EXPIRES_SECONDS,
            'sent_at' => time(),
            'attempts' => 0,
        ];

        RateLimiter::hit('voter_ballot_code_send', $identifier, self::BALLOT_CODE_EXPIRES_SECONDS);

        $this->jsonResponse([
            'success' => true,
            'message' => 'Type the displayed 6-digit number below to confirm this ballot. Your receipt will be sent to ' . $this->maskEmail($email) . ' after submission.',
            'code' => $code,
            'expires_in' => self::BALLOT_CODE_EXPIRES_SECONDS,
            'resend_in' => self::BALLOT_CODE_RESEND_SECONDS,
        ]);
    }

    public function submit(): void
    {
        $this->requirePost();
        $voterId = $this->verifiedVoterId();

        if (!$voterId) {
            voting_flash('warning', 'Please verify your voter information first.');
            $this->redirect('/');
        }

        $election = (new Election())->current();

        if (!$election || $election['status'] !== 'open') {
            voting_flash('warning', 'Voting is not open right now.');
            $this->redirect('/');
        }

        if (!$this->verifyBallotCode((int) $voterId, (int) $election['id'])) {
            $this->redirect('/vote/ballot');
        }

        try {
            $reference = (new Vote())->submit(
                (int) $election['id'],
                (int) $voterId,
                $_POST['choices'] ?? []
            );
            $submittedVoter = (new Voter())->find((int) $voterId);

            $receiptEmail = trim((string) ($submittedVoter['email'] ?? ''));

            if ($submittedVoter && filter_var($receiptEmail, FILTER_VALIDATE_EMAIL) !== false) {
                try {
                    $sent = (new Mailer())->sendVoteReceipt(
                        $receiptEmail,
                        $submittedVoter,
                        $election,
                        $reference,
                        (string) ($submittedVoter['voted_at'] ?? date('Y-m-d H:i:s'))
                    );

                    if (!$sent) {
                        error_log('Vote receipt email failed for reference ' . $reference . '.');
                    }
                } catch (\Throwable $exception) {
                    error_log('Vote receipt email failed for reference ' . $reference . ': ' . $exception->getMessage());
                }
            } else {
                error_log('Vote receipt email skipped for reference ' . $reference . ' because the voter email is invalid.');
            }

            (new AuditLog())->record('vote_submitted', 'Ballot reference ' . $reference . ' was submitted.');
            $_SESSION['last_vote_reference'] = $reference;
            unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_ballot_code']);
            $this->redirect('/vote/receipt');
        } catch (InvalidArgumentException $exception) {
            voting_flash('error', $exception->getMessage());
            $this->redirect('/vote/ballot');
        } catch (RuntimeException $exception) {
            voting_flash('warning', $exception->getMessage());
            unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_ballot_code']);
            $this->redirect('/');
        }
    }

    public function receipt(): void
    {
        $reference = $_SESSION['last_vote_reference'] ?? null;

        if (!$reference) {
            $this->redirect('/');
        }

        unset($_SESSION['last_vote_reference']);

        $receipt = null;
        try {
            $pdo = \App\VotingSystem\Core\Database::connection();
            $statement = $pdo->prepare(
                'SELECT reference_code, previous_hash, block_hash, ballot_root, voter_commitment, nodes_confirmed, created_at
                 FROM vote_receipts
                 WHERE reference_code = :reference_code
                 LIMIT 1'
            );
            $statement->execute(['reference_code' => $reference]);
            $receipt = $statement->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable) {
            $receipt = null;
        }

        $this->view('voter/receipt', [
            'title' => 'Vote Submitted',
            'reference' => $reference,
            'receipt' => $receipt,
        ]);
    }

    private function isAllowedSchoolEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        $domain = $this->allowedSchoolEmailDomain();

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && $domain !== ''
            && str_ends_with($email, '@' . $domain);
    }

    private function allowedSchoolEmailDomain(): string
    {
        return ltrim(strtolower(trim((string) voting_config('google.allowed_domain', 'g.batstate-u.edu.ph'))), '@');
    }

    private function schoolEmailErrorMessage(): string
    {
        return 'Please use your official BatStateU Google Workspace email (@' . $this->allowedSchoolEmailDomain() . ').';
    }

    private function clearGoogleOAuthState(): void
    {
        unset(
            $_SESSION['google_oauth_state'],
            $_SESSION['google_oauth_nonce'],
            $_SESSION['google_oauth_started_at']
        );
    }

    private function verifiedVoterId(): ?int
    {
        $voterId = (int) ($_SESSION['verified_voter_id'] ?? 0);

        if ($voterId <= 0) {
            return null;
        }

        $now = time();
        $loginAt = (int) ($_SESSION['verified_voter_login_at'] ?? $now);
        $lastSeen = (int) ($_SESSION['verified_voter_last_seen'] ?? $now);
        $idleLimit = max(300, (int) voting_config('security.session_idle_seconds', 1800));
        $absoluteLimit = max($idleLimit, min(7200, (int) voting_config('security.session_absolute_seconds', 28800)));

        if ($now - $lastSeen > $idleLimit
            || $now - $loginAt > $absoluteLimit
        ) {
            unset(
                $_SESSION['verified_voter_id'],
                $_SESSION['verified_voter_login_at'],
                $_SESSION['verified_voter_last_seen'],
                $_SESSION['verified_voter_ua_hash'],
                $_SESSION['pending_voter_ballot_code']
            );

            return null;
        }

        $_SESSION['verified_voter_login_at'] = $loginAt;
        $_SESSION['verified_voter_last_seen'] = $now;
        $_SESSION['verified_voter_ua_hash'] = $this->userAgentHash();

        return $voterId;
    }

    private function verifyBallotCode(int $voterId, int $electionId): bool
    {
        $pending = $_SESSION['pending_voter_ballot_code'] ?? null;
        $identifier = $this->ballotCodeIdentifier($voterId);

        if (!$pending) {
            voting_flash('error', 'Please generate and enter the displayed verification number before submitting your ballot.');
            return false;
        }

        if ((int) ($pending['voter_id'] ?? 0) !== $voterId || (int) ($pending['election_id'] ?? 0) !== $electionId) {
            unset($_SESSION['pending_voter_ballot_code']);
            voting_flash('error', 'Your verification number no longer matches this ballot session. Please generate a new number.');
            return false;
        }

        if ((int) ($pending['expires_at'] ?? 0) < time()) {
            unset($_SESSION['pending_voter_ballot_code']);
            voting_flash('error', 'Your ballot verification number expired. Please generate a new number.');
            return false;
        }

        if (RateLimiter::tooManyAttempts('voter_ballot_code_verify', $identifier, 6, self::BALLOT_CODE_EXPIRES_SECONDS)) {
            unset($_SESSION['pending_voter_ballot_code']);
            voting_flash('error', 'Too many incorrect verification numbers. Please generate a new number.');
            return false;
        }

        $code = preg_replace('/\D+/', '', (string) ($_POST['ballot_code'] ?? ''));

        if ($code !== '' && password_verify($code, (string) ($pending['code_hash'] ?? ''))) {
            RateLimiter::clear('voter_ballot_code_verify', $identifier);
            return true;
        }

        RateLimiter::hit('voter_ballot_code_verify', $identifier, self::BALLOT_CODE_EXPIRES_SECONDS);
        $_SESSION['pending_voter_ballot_code']['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
        voting_flash('error', 'Invalid ballot verification number. Please type the displayed number and try again.');
        return false;
    }

    private function ballotCodeIdentifier(int $voterId): string
    {
        return $voterId . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function userAgentHash(): string
    {
        return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = substr($name, 0, 2);

        return $visible . str_repeat('*', max(2, strlen($name) - 2)) . ($domain !== '' ? '@' . $domain : '');
    }

    private function jsonResponse(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload);
        exit;
    }
}
