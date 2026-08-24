<?php

namespace App\VotingSystem\Controllers;

use App\VotingSystem\Core\Controller;
use App\VotingSystem\Core\Mailer;
use App\VotingSystem\Core\VoteBlockchain;
use App\VotingSystem\Models\AdminUser;
use App\VotingSystem\Models\AuditLog;
use App\VotingSystem\Models\Candidate;
use App\VotingSystem\Models\Dashboard;
use App\VotingSystem\Models\Election;
use App\VotingSystem\Models\Position;
use App\VotingSystem\Models\SecurityEvent;
use App\VotingSystem\Models\Voter;
use App\VotingSystem\Models\Vote;

class AdminController extends Controller
{
    private const CANDIDATE_PHOTO_MAX_BYTES = 2 * 1024 * 1024;
    private const CANDIDATE_PHOTO_MAX_MB = 2;

    public function dashboard(): void
    {
        $user = $this->requireAuth(['admin', 'canvassing', 'view_only']);
        $this->redirectStaffFromAdminPath($user, canvassing_dashboard_path());
        [$election, $dashboard] = $this->dashboardContext();

        $college = $_GET['college'] ?? null;
        $program = $_GET['program'] ?? null;
        $position = $_GET['position'] ?? null;
        $yearLevel = $_GET['year_level'] ?? null;

        $this->view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'election' => $election,
            'summary' => $dashboard->summary((int) $election['id']),
            'turnoutByCollege' => $dashboard->turnoutByCollege(),
            'turnoutByProgram' => $dashboard->turnoutByProgram(),
            'turnoutByGradeLevel' => $dashboard->turnoutByGradeLevel(),
            'programFilterOptions' => $dashboard->programOptionsByCollege(),
            'positionOptions' => (new Position())->forElection((int) $election['id']),
            'results' => $dashboard->results((int) $election['id'], $college, $position, $yearLevel, $program),
            'recentVotes' => $dashboard->recentVotes(),
            'recentActivity' => $dashboard->recentActivity(),
            'filterCollege' => $college,
            'filterProgram' => $program,
            'filterPosition' => $position,
            'filterYearLevel' => $yearLevel,
        ], 'admin');
    }

    public function candidates(): void
    {
        $this->requireAuth(['admin']);
        $election = (new Election())->current();

        $this->view('admin/candidates', [
            'title' => 'Candidate Management',
            'election' => $election,
            'positions' => $election ? (new Position())->forElection((int) $election['id']) : [],
        ], 'admin');
    }

    public function canvassingDashboard(): void
    {
        $this->requireAuth(['admin', 'canvassing', 'view_only']);
        [$election, $dashboard] = $this->dashboardContext();

        $this->view('admin/canvassing_dashboard', [
            'title' => 'Canvassing Dashboard',
            'election' => $election,
            'summary' => $dashboard->summary((int) $election['id']),
            'turnoutByCollege' => $dashboard->turnoutByCollege(),
            'turnoutByProgram' => $dashboard->turnoutByProgram(),
            'turnoutByGradeLevel' => $dashboard->turnoutByGradeLevel(),
            'results' => $dashboard->results((int) $election['id']),
            'recentVotes' => $dashboard->recentVotes(6),
        ], 'admin');
    }

    public function canvassing(): void
    {
        $this->requireAuth(['admin', 'canvassing', 'view_only']);
        [$election, $dashboard] = $this->dashboardContext();

        $college = $_GET['college'] ?? null;
        $program = $_GET['program'] ?? null;
        $position = $_GET['position'] ?? null;
        $yearLevel = $_GET['year_level'] ?? null;

        $this->view('admin/canvassing', [
            'title' => 'Canvassing',
            'election' => $election,
            'summary' => $dashboard->summary((int) $election['id']),
            'turnoutByCollege' => $dashboard->turnoutByCollege(),
            'turnoutByProgram' => $dashboard->turnoutByProgram(),
            'turnoutByGradeLevel' => $dashboard->turnoutByGradeLevel(),
            'programFilterOptions' => $dashboard->programOptionsByCollege(),
            'positionOptions' => (new Position())->forElection((int) $election['id']),
            'results' => $dashboard->results((int) $election['id'], $college, $position, $yearLevel, $program),
            'recentVotes' => $dashboard->recentVotes(12),
            'filterCollege' => $college,
            'filterProgram' => $program,
            'filterPosition' => $position,
            'filterYearLevel' => $yearLevel,
        ], 'admin');
    }

    public function storeCandidate(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();
        $this->rememberOldInput();

        if (empty($_POST['position_id']) || empty($_POST['name'])) {
            voting_flash('error', 'Candidate position and name are required.');
            $this->redirect('/admin/candidates');
        }

        $uploadedPhoto = $this->readCandidatePhotoUpload();

        if (!$uploadedPhoto['uploaded']) {
            voting_flash('error', 'Please upload a valid JPG or PNG image (maximum ' . self::CANDIDATE_PHOTO_MAX_MB . ' MB).');
            $this->redirect('/admin/candidates');
        }

        if (!$uploadedPhoto['valid']) {
            voting_flash('error', $uploadedPhoto['error']);
            $this->redirect('/admin/candidates');
        }

        $payload = $_POST;
        $payload['image_path'] = '';
        $payload['image_blob'] = $uploadedPhoto['contents'];
        $payload['image_mime'] = $uploadedPhoto['mime'];
        (new Candidate())->create($payload);

        (new AuditLog())->record('candidate_created', 'Candidate ' . trim($_POST['name']) . ' was added.');
        voting_flash('success', 'Candidate added.');
        unset($_SESSION['_old']);
        $this->redirect('/admin/candidates');
    }

    public function updateCandidate(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();
        $this->rememberOldInput();

        $candidateId = (int) ($_POST['candidate_id'] ?? 0);

        $model = new Candidate();
        $existing = $model->find($candidateId);

        if (!$existing || empty($_POST['position_id']) || trim((string) ($_POST['name'] ?? '')) === '') {
            voting_flash('error', 'Candidate could not be updated. Check the entries and try again.');
            $this->redirect('/admin/candidates');
        }

        $imagePath = (string) ($existing['image_path'] ?? '');
        $uploadedPhoto = $this->readCandidatePhotoUpload();

        if (!$uploadedPhoto['valid']) {
            voting_flash('error', $uploadedPhoto['error']);
            $this->redirect('/admin/candidates');
        }

        $payload = [
            'position_id' => $_POST['position_id'],
            'name' => $_POST['name'],
            'party' => trim((string) ($_POST['party'] ?? '')),
            'sort_order' => $_POST['sort_order'] ?? 0,
            'image_path' => $imagePath,
        ];

        if ($uploadedPhoto['uploaded']) {
            $payload['image_path'] = '';
            $payload['image_blob'] = $uploadedPhoto['contents'];
            $payload['image_mime'] = $uploadedPhoto['mime'];
        }

        $model->update($candidateId, $payload);
        (new AuditLog())->record('candidate_updated', 'Candidate ' . trim((string) $_POST['name']) . ' was updated.');
        voting_flash('success', 'Candidate updated.');
        unset($_SESSION['_old']);
        $this->redirect('/admin/candidates');
    }

    public function deleteCandidate(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();

        $candidateId = (int) ($_POST['candidate_id'] ?? 0);
        $model = new Candidate();
        $record = $model->find($candidateId);

        if (!$record) {
            voting_flash('warning', 'That candidate entry was already removed.');
            $this->redirect('/admin/candidates');
        }

        $label = trim((string) ($record['name'] ?? ''));

        if (!$model->delete($candidateId)) {
            voting_flash('error', 'The candidate entry could not be deleted.');
            $this->redirect('/admin/candidates');
        }

        (new AuditLog())->record('candidate_deleted', $label !== '' ? 'Candidate "' . $label . '" deleted.' : 'Candidate deleted.');
        voting_flash('success', 'Candidate removed.');
        $this->redirect('/admin/candidates');
    }

    public function voters(): void
    {
        $this->requireAuth(['admin']);
        $voterModel = new Voter();
        $filterSrCode = trim((string) ($_GET['sr_code'] ?? ''));
        $filterCollege = trim((string) ($_GET['college'] ?? ''));

        $this->view('admin/voters', [
            'title' => 'Voter List',
            'voters' => $voterModel->filtered($filterSrCode, $filterCollege, 250),
            'voterCount' => $voterModel->filteredCount($filterSrCode, $filterCollege),
            'collegeOptions' => $voterModel->colleges(),
            'filterSrCode' => $filterSrCode,
            'filterCollege' => $filterCollege,
        ], 'admin');
    }

    public function canvassingAccount(): void
    {
        $this->requireAuth(['admin']);

        $this->view('admin/canvassing_account', [
            'title' => 'Canvassing Account',
            'accounts' => (new AdminUser())->canvassingAccounts(),
        ], 'admin');
    }

    public function storeCanvassingAccount(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();
        $this->rememberOldInput();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = $this->generateStaffPassword();
        $role = (string) ($_POST['role'] ?? 'canvassing');
        $isActive = 1;

        if (!$this->validateStaffAccountInput($name, $email, $role, $password, true)) {
            $this->redirect('/admin/canvassing-account');
        }

        $adminUsers = new AdminUser();
        if ($adminUsers->findByEmail($email)) {
            voting_flash('error', 'That email is already used by another admin account.');
            $this->redirect('/admin/canvassing-account');
        }

        $adminUsers->createStaffAccount([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'is_active' => $isActive,
        ]);

        (new AuditLog())->record('canvassing_account_created', 'Canvassing account ' . $email . ' was created.');
        $credentialsSent = (new Mailer())->sendStaffCredentials(
            $email,
            $name,
            $password,
            $role,
            $this->staffLoginUrl()
        );

        if ($credentialsSent) {
            voting_flash('success', 'Canvassing account created. The login email and generated password were sent to ' . $email . '.');
        } else {
            voting_flash('warning', 'Canvassing account created, but the credential email could not be sent. Reset the password manually before sharing access.');
        }

        unset($_SESSION['_old']);
        $this->redirect('/admin/canvassing-account');
    }

    public function updateCanvassingAccount(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = trim((string) ($_POST['password'] ?? ''));
        $role = (string) ($_POST['role'] ?? 'canvassing');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($accountId <= 0 || !$this->validateStaffAccountInput($name, $email, $role, $password, false)) {
            $this->redirect('/admin/canvassing-account');
        }

        $adminUsers = new AdminUser();
        $existing = $adminUsers->find($accountId);

        if (!$existing || !in_array((string) ($existing['role'] ?? ''), ['canvassing', 'view_only'], true)) {
            voting_flash('error', 'Canvassing account not found.');
            $this->redirect('/admin/canvassing-account');
        }

        $duplicate = $adminUsers->findByEmail($email);
        if ($duplicate && (int) $duplicate['id'] !== $accountId) {
            voting_flash('error', 'That email is already used by another admin account.');
            $this->redirect('/admin/canvassing-account');
        }

        $adminUsers->updateStaffAccount($accountId, [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'is_active' => $isActive,
        ]);

        (new AuditLog())->record('canvassing_account_updated', 'Canvassing account ' . $email . ' was updated.');
        voting_flash('success', 'Canvassing account updated.');
        $this->redirect('/admin/canvassing-account');
    }

    public function deleteCanvassingAccount(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $adminUsers = new AdminUser();
        $existing = $adminUsers->find($accountId);

        if (!$existing || !in_array((string) ($existing['role'] ?? ''), ['canvassing', 'view_only'], true)) {
            voting_flash('warning', 'That canvassing account was already removed.');
            $this->redirect('/admin/canvassing-account');
        }

        $email = (string) ($existing['email'] ?? '');

        if (!$adminUsers->deleteStaffAccount($accountId)) {
            voting_flash('error', 'The canvassing account could not be deleted.');
            $this->redirect('/admin/canvassing-account');
        }

        (new AuditLog())->record('canvassing_account_deleted', 'Canvassing account ' . $email . ' was deleted.');
        voting_flash('success', 'Canvassing account deleted.');
        $this->redirect('/admin/canvassing-account');
    }

    public function addVoter(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();
        $this->rememberOldInput();

        if (!$this->validateVoterPayload($_POST)) {
            $this->redirect('/admin/voters');
        }

        (new Voter())->upsert($_POST);
        (new AuditLog())->record('voter_upserted', 'Voter ' . trim($_POST['sr_code']) . ' was added or updated.');
        voting_flash('success', 'Voter saved.');
        unset($_SESSION['_old']);
        $this->redirect('/admin/voters');
    }

    public function updateVoter(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();
        $this->rememberOldInput();

        $voterId = (int) ($_POST['voter_id'] ?? 0);
        $voterModel = new Voter();
        $voter = $voterModel->find($voterId);

        if (!$voter) {
            voting_flash('warning', 'That voter record was already removed.');
            $this->redirect('/admin/voters');
        }

        if (!$this->validateVoterPayload($_POST)) {
            $this->redirect('/admin/voters');
        }

        $duplicate = $voterModel->findBySrCode((string) ($_POST['sr_code'] ?? ''));
        if ($duplicate && (int) ($duplicate['id'] ?? 0) !== $voterId) {
            voting_flash('error', 'That SR Code is already assigned to another voter.');
            $this->redirect('/admin/voters');
        }

        $voterModel->update($voterId, $_POST);
        (new AuditLog())->record('voter_updated', 'Voter ' . trim((string) $_POST['sr_code']) . ' was updated.');
        voting_flash('success', 'Voter information updated.');
        unset($_SESSION['_old']);
        $this->redirect('/admin/voters');
    }

    public function resetVoterVote(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();

        $voterId = (int) ($_POST['voter_id'] ?? 0);
        $voterModel = new Voter();
        $voter = $voterModel->find($voterId);

        if (!$voter) {
            voting_flash('warning', 'That voter record was already removed.');
            $this->redirect('/admin/voters');
        }

        (new Vote())->resetForVoter($voterId);

        $label = trim((string) ($voter['sr_code'] ?? ''));
        (new AuditLog())->record('voter_vote_reset', 'Vote reset for voter ' . ($label !== '' ? $label : '#' . $voterId) . '.');
        voting_flash('success', 'Vote reset for ' . trim((string) ($voter['full_name'] ?? 'this voter')) . '. They can submit a ballot again.');
        $this->redirect('/admin/voters');
    }

    public function resetAllVotes(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();

        $summary = (new Vote())->resetAll();

        (new AuditLog())->record(
            'all_votes_reset',
            'All votes were reset. Vote rows deleted: ' . $summary['votes_deleted'] . '; receipts deleted: ' . $summary['receipts_deleted'] . '; voters reset: ' . $summary['voters_reset'] . '.'
        );
        voting_flash('success', 'All votes were reset. Voters can submit ballots again.');
        $this->redirect('/admin/voters');
    }

    public function deleteVoter(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();

        $voterId = (int) ($_POST['voter_id'] ?? 0);
        $voterModel = new Voter();
        $voter = $voterModel->find($voterId);

        if (!$voter) {
            voting_flash('warning', 'That voter record was already removed.');
            $this->redirect('/admin/voters');
        }

        if (!$voterModel->delete($voterId)) {
            voting_flash('error', 'The voter record could not be deleted.');
            $this->redirect('/admin/voters');
        }

        $label = trim((string) ($voter['sr_code'] ?? ''));
        (new AuditLog())->record('voter_deleted', 'Voter ' . ($label !== '' ? $label : '#' . $voterId) . ' was deleted.');
        voting_flash('success', 'Voter deleted. Any ballot linked to that voter was also removed.');
        $this->redirect('/admin/voters');
    }

    public function deleteAllVoters(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();

        $count = (new Voter())->deleteAll();

        (new AuditLog())->record('all_voters_deleted', $count . ' voter records were deleted.');
        voting_flash('success', $count . ' voter record(s) deleted. Linked ballots and receipts were also removed.');
        $this->redirect('/admin/voters');
    }

    public function importVoters(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();

        // Allow large imports
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        if (
            ($_FILES['voter_csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || empty($_FILES['voter_csv']['tmp_name'])
            || !is_uploaded_file($_FILES['voter_csv']['tmp_name'])
        ) {
            voting_flash('error', 'Please upload a CSV file.');
            $this->redirect('/admin/voters');
        }

        if (($_FILES['voter_csv']['size'] ?? 0) > 10 * 1024 * 1024) {
            voting_flash('error', 'CSV file is too large. Please upload a file below 10 MB.');
            $this->redirect('/admin/voters');
        }

        $extension = strtolower(pathinfo($_FILES['voter_csv']['name'] ?? '', PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            voting_flash('error', 'Please upload a .csv file.');
            $this->redirect('/admin/voters');
        }

        $firstBytes = file_get_contents($_FILES['voter_csv']['tmp_name'], false, null, 0, 4096);
        if ($firstBytes === false || str_contains($firstBytes, "\0")) {
            voting_flash('error', 'CSV file appears invalid. Please upload a plain text .csv file.');
            $this->redirect('/admin/voters');
        }

        $handle = fopen($_FILES['voter_csv']['tmp_name'], 'r');
        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            voting_flash('error', 'CSV file is empty or invalid.');
            $this->redirect('/admin/voters');
        }

        // Strip BOM from first header if present
        if (!empty($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        $rows = [];
        $rowLimit = 20000;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($rows) >= $rowLimit) {
                fclose($handle);
                voting_flash('error', 'CSV import is limited to ' . $rowLimit . ' rows per upload.');
                $this->redirect('/admin/voters');
            }

            if (count($row) !== count($headers)) {
                continue;
            }

            $data = array_combine($headers, $row);

            $mapped = [
                'sr_code' => $data['SR-Code'] ?? $data['sr_code'] ?? '',
                'full_name' => $data['Full Name'] ?? $data['full_name'] ?? '',
                'email' => $data['Email Address'] ?? $data['email'] ?? '',
                'college' => $data['Department'] ?? $data['College/Department'] ?? $data['College'] ?? $data['college'] ?? '',
                'program' => $data['Program'] ?? $data['program'] ?? '',
                'year_level' => $data['Year Level'] ?? $data['year_level'] ?? '',
                'grade_level' => $data['Grade Level'] ?? $data['grade_level'] ?? '',
            ];

            if ($this->isValidImportedVoter($mapped)) {
                $rows[] = $mapped;
            }
        }

        fclose($handle);

        $count = (new Voter())->bulkUpsert($rows);

        (new AuditLog())->record('voters_imported', $count . ' voter rows were imported.');
        voting_flash('success', $count . ' voter record(s) imported successfully.');
        $this->redirect('/admin/voters');
    }

    public function reports(): void
    {
        $user = $this->requireAuth(['admin', 'canvassing', 'view_only']);
        $this->redirectStaffFromAdminPath($user, canvassing_reports_path());
        [$election, $dashboard] = $this->dashboardContext();

        $this->view('admin/reports', [
            'title' => 'Printable Reports',
            'election' => $election,
            'summary' => $dashboard->summary((int) $election['id']),
            'turnoutByCollege' => $dashboard->turnoutByCollege(),
            'results' => $dashboard->results((int) $election['id']),
        ], 'admin');
    }

    public function canvassingReportsGate(): void
    {
        $user = $this->requireAuth(['admin', 'canvassing', 'view_only']);

        $this->view('admin/reports_pin', [
            'title' => 'Reports Access',
        ], 'admin');
    }

    public function canvassingReportsVerifyPin(): void
    {
        $this->requireAuth(['admin', 'canvassing', 'view_only']);
        $this->requirePost();

        $submittedPin = trim((string) ($_POST['pin'] ?? ''));
        $correctPin = trim((string) env_value('CANVASSING_REPORTS_PIN', ''));

        if ($correctPin === '' || !hash_equals($correctPin, $submittedPin)) {
            voting_flash('error', 'Incorrect PIN. Access denied.');
            $this->redirect(canvassing_reports_path());
        }

        [$election, $dashboard] = $this->dashboardContext();
        $this->view('admin/reports', [
            'title' => 'Printable Reports',
            'election' => $election,
            'summary' => $dashboard->summary((int) $election['id']),
            'turnoutByCollege' => $dashboard->turnoutByCollege(),
            'results' => $dashboard->results((int) $election['id']),
        ], 'admin');
    }

    public function electionSettings(): void
    {
        $this->requireAuth(['admin']);
        $election = (new Election())->current();

        if (!$election) {
            voting_flash('warning', 'No election record has been configured yet.');
            $this->redirect('/');
        }

        $this->view('admin/election_settings', [
            'title' => 'Election schedule',
            'election' => $election,
            'timezone' => voting_config('timezone', 'Asia/Manila'),
            'start_at_value' => $this->datetimeLocalForInput($election['start_at'] ?? null),
            'end_at_value' => $this->datetimeLocalForInput($election['end_at'] ?? null),
            'announcement_expires_at_value' => $this->datetimeLocalForInput($election['announcement_expires_at'] ?? null),
        ], 'admin');
    }

    public function updateElectionSettings(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();
        $this->rememberOldInput();

        $election = (new Election())->current();

        if (!$election) {
            voting_flash('warning', 'No election record has been configured yet.');
            $this->redirect('/');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $status = (string) ($_POST['status'] ?? '');
        $allowedStatus = ['pending', 'open', 'closed'];

        if ($title === '') {
            voting_flash('error', 'Election title is required.');
            $this->redirect('/admin/election');
        }

        if (!in_array($status, $allowedStatus, true)) {
            voting_flash('error', 'Invalid election status.');
            $this->redirect('/admin/election');
        }

        $startAt = $this->parseDatetimeLocalInput((string) ($_POST['start_at'] ?? ''));
        $endAt = $this->parseDatetimeLocalInput((string) ($_POST['end_at'] ?? ''));

        if ($startAt !== null && $endAt !== null && strtotime($endAt) < strtotime($startAt)) {
            voting_flash('error', 'End date and time must be on or after the start.');
            $this->redirect('/admin/election');
        }

        $instructions = trim((string) ($_POST['instructions'] ?? ''));
        $announcement = trim((string) ($_POST['announcement'] ?? ''));
        $announcementExpiresAt = $this->parseDatetimeLocalInput((string) ($_POST['announcement_expires_at'] ?? ''));

        (new Election())->updateSettings((int) $election['id'], [
            'title' => $title,
            'status' => $status,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'announcement' => $announcement === '' ? null : $announcement,
            'announcement_expires_at' => $announcement === '' ? null : $announcementExpiresAt,
            'instructions' => $instructions === '' ? null : $instructions,
        ]);

        (new AuditLog())->record('election_settings_updated', 'Election schedule and status were updated (status: ' . $status . ').');
        voting_flash('success', 'Election settings saved. Voting access now follows the selected status immediately.');
        unset($_SESSION['_old']);
        $this->redirect('/admin/election');
    }

    public function ballotContent(): void
    {
        $this->requireAuth(['admin']);
        $election = (new Election())->current();

        if (!$election) {
            voting_flash('warning', 'No election record has been configured yet.');
            $this->redirect('/');
        }

        $this->view('admin/ballot_content', [
            'title' => 'Ballot intro card',
            'election' => $election,
        ], 'admin');
    }

    public function updateBallotContent(): void
    {
        $this->requireAuth(['admin']);
        $this->requirePost();

        $election = (new Election())->current();

        if (!$election) {
            voting_flash('warning', 'No election record has been configured yet.');
            $this->redirect('/');
        }

        $electionId = (int) $election['id'];
        $uploaded = $this->storeUploadedBallotCardImage();
        $existingImage = trim((string) ($election['ballot_card_image_path'] ?? ''));
        $imagePath = $uploaded !== '' ? $uploaded : $existingImage;

        $payload = [
            'ballot_card_kicker' => trim((string) ($_POST['ballot_card_kicker'] ?? '')),
            'ballot_card_heading' => trim((string) ($_POST['ballot_card_heading'] ?? '')),
            'ballot_card_body' => trim((string) ($_POST['ballot_card_body'] ?? '')),
            'ballot_card_image_path' => $imagePath,
        ];

        if ($payload['ballot_card_heading'] === '' || $payload['ballot_card_body'] === '') {
            voting_flash('error', 'Card heading and description are required.');
            $this->redirect('/admin/ballot-content');
        }

        (new Election())->updateBallotContent($electionId, $payload);

        (new AuditLog())->record('ballot_card_updated', 'Ballot intro card content was updated.');
        voting_flash('success', 'Ballot intro card saved.');
        $this->redirect('/admin/ballot-content');
    }

    public function security(): void
    {
        $this->requireAuth(['admin']);
        $events = new SecurityEvent();

        $this->view('admin/security', [
            'title' => 'Security Monitor',
            'summary' => $events->summary(),
            'topSources' => $events->topSources(),
            'recentEvents' => $events->recent(120),
            'globalLimit' => (int) voting_config('security.global_rate_limit', 180),
            'globalWindow' => (int) voting_config('security.global_rate_window', 60),
            'sensitiveLimit' => (int) voting_config('security.sensitive_rate_limit', 35),
            'sensitiveWindow' => (int) voting_config('security.sensitive_rate_window', 300),
        ], 'admin');
    }

    public function chainVerify(): void
    {
        $this->requireAuth(['admin']);

        $reference = trim((string) ($_GET['reference'] ?? $_POST['reference'] ?? ''));
        $result = null;

        if ($reference !== '') {
            $result = (new VoteBlockchain())->verify($reference);
            (new AuditLog())->record(
                'chain_verify',
                'Chain verify for '.$reference.': '.(($result['ok'] ?? false) ? 'pass' : 'fail')
            );
        }

        $this->view('admin/chain_verify', [
            'title' => 'Chain Integrity',
            'reference' => $reference,
            'result' => $result,
        ], 'admin');
    }

    private function dashboardContext(): array
    {
        $election = (new Election())->current();

        if (!$election) {
            voting_flash('warning', 'No election record has been configured yet.');
            $this->redirect('/');
        }

        return [$election, new Dashboard()];
    }

    private function redirectStaffFromAdminPath(array $user, string $privatePath): void
    {
        if (($user['role'] ?? '') === 'admin') {
            return;
        }

        if (str_starts_with(voting_current_path(), '/admin')) {
            $this->redirect($privatePath);
        }
    }

    private function validateStaffAccountInput(string $name, string $email, string $role, string $password, bool $passwordRequired): bool
    {
        if ($name === '' || $email === '') {
            voting_flash('error', 'Name and email are required.');
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            voting_flash('error', 'Please enter a valid email address.');
            return false;
        }

        if (!in_array($role, ['canvassing', 'view_only'], true)) {
            voting_flash('error', 'Invalid canvassing account role.');
            return false;
        }

        if ($passwordRequired && trim($password) === '') {
            voting_flash('error', 'Password is required for a new canvassing account.');
            return false;
        }

        if (trim($password) !== '' && strlen($password) < 8) {
            voting_flash('error', 'Password must be at least 8 characters.');
            return false;
        }

        return true;
    }

    private function generateStaffPassword(int $length = 16): string
    {
        $sets = [
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'abcdefghijkmnopqrstuvwxyz',
            '23456789',
            '!@#$%^&*()-_=+',
        ];

        $characters = [];

        foreach ($sets as $set) {
            $characters[] = $set[random_int(0, strlen($set) - 1)];
        }

        $all = implode('', $sets);

        while (count($characters) < $length) {
            $characters[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($characters) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
        }

        return implode('', $characters);
    }

    private function staffLoginUrl(): string
    {
        $configuredUrl = trim((string) voting_config('security.staff_login_url', ''));

        return $configuredUrl !== '' ? $configuredUrl : voting_url(admin_login_path());
    }

    private function validateVoterPayload(array $payload): bool
    {
        $srCode = trim((string) ($payload['sr_code'] ?? ''));
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $college = trim((string) ($payload['college'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));

        if ($srCode === '' || $fullName === '' || $college === '') {
            voting_flash('error', 'SR Code, full name, and college are required.');
            return false;
        }

        if (!$this->isAllowedVoterEmail($email)) {
            voting_flash('error', 'Please enter a valid official BatStateU Google Workspace email address.');
            return false;
        }

        return true;
    }

    private function isValidImportedVoter(array $payload): bool
    {
        return trim((string) ($payload['sr_code'] ?? '')) !== ''
            && trim((string) ($payload['full_name'] ?? '')) !== ''
            && trim((string) ($payload['college'] ?? '')) !== ''
            && $this->isAllowedVoterEmail((string) ($payload['email'] ?? ''));
    }

    private function isAllowedVoterEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        $domain = ltrim(strtolower(trim((string) voting_config('google.allowed_domain', 'g.batstate-u.edu.ph'))), '@');

        return $domain !== ''
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && str_ends_with($email, '@' . $domain);
    }

    private function datetimeLocalForInput(?string $mysqlDatetime): string
    {
        if ($mysqlDatetime === null || trim($mysqlDatetime) === '') {
            return '';
        }

        $clean = substr(trim($mysqlDatetime), 0, 19);

        return str_contains($clean, ' ') ? str_replace(' ', 'T', $clean) : $clean;
    }

    private function parseDatetimeLocalInput(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        if (!preg_match('/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})(?::(\d{2}))?$/', $raw, $matches)) {
            return null;
        }

        $seconds = $matches[3] ?? null;

        return $matches[1] . ' ' . $matches[2] . ($seconds !== null && $seconds !== '' ? ':' . $seconds : ':00');
    }

    private function storeUploadedBallotCardImage(): string
    {
        return $this->storeUploadedImage('ballot_card_image', 'img/uploads/ballot', 'ballot');
    }

    private function readCandidatePhotoUpload(): array
    {
        $file = $_FILES['candidate_photo'] ?? null;

        if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [
                'uploaded' => false,
                'valid' => true,
                'contents' => null,
                'mime' => '',
                'error' => '',
            ];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return [
                'uploaded' => true,
                'valid' => false,
                'contents' => null,
                'mime' => '',
                'error' => 'The candidate photo could not be uploaded. Please choose a JPG or PNG below ' . self::CANDIDATE_PHOTO_MAX_MB . ' MB.',
            ];
        }

        if (($file['size'] ?? 0) > self::CANDIDATE_PHOTO_MAX_BYTES) {
            return [
                'uploaded' => true,
                'valid' => false,
                'contents' => null,
                'mime' => '',
                'error' => 'Candidate photo is too large. Maximum size is ' . self::CANDIDATE_PHOTO_MAX_MB . ' MB.',
            ];
        }

        static $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        $ext = $extensionMap[$mime] ?? '';

        if ($ext === '') {
            return [
                'uploaded' => true,
                'valid' => false,
                'contents' => null,
                'mime' => '',
                'error' => 'Please upload a valid JPG or PNG candidate photo.',
            ];
        }

        $imageInfo = @getimagesize($file['tmp_name']);

        if ($imageInfo === false || !isset($imageInfo[0], $imageInfo[1]) || (int) $imageInfo[0] > 8000 || (int) $imageInfo[1] > 8000) {
            return [
                'uploaded' => true,
                'valid' => false,
                'contents' => null,
                'mime' => '',
                'error' => 'Please upload a readable JPG or PNG image no larger than 8000 pixels per side.',
            ];
        }

        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false) {
            return [
                'uploaded' => true,
                'valid' => false,
                'contents' => null,
                'mime' => '',
                'error' => 'The candidate photo could not be read. Please try another JPG or PNG image.',
            ];
        }

        return [
            'uploaded' => true,
            'valid' => true,
            'contents' => $contents,
            'mime' => $mime,
            'error' => '',
        ];
    }

    /**
     * Save an uploaded JPG/PNG (max 2MB) under public/assets/{relativeDir} and
     * return the public-relative path used by the voting_asset() helper.
     */
    private function storeUploadedImage(string $field, string $relativeDir, string $prefix): string
    {
        $file = $_FILES[$field] ?? null;

        if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            return '';
        }

        if (!is_uploaded_file($file['tmp_name']) || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return '';
        }

        static $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        $ext = $extensionMap[$mime] ?? '';

        if ($ext === '') {
            return '';
        }

        $imageInfo = @getimagesize($file['tmp_name']);

        if ($imageInfo === false || !isset($imageInfo[0], $imageInfo[1]) || (int) $imageInfo[0] > 8000 || (int) $imageInfo[1] > 8000) {
            return '';
        }

        $directory = voting_public_assets_path(trim($relativeDir, '/'));

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return '';
        }

        $filename = $prefix . '-' . preg_replace('/[^a-zA-Z0-9]/', '-', uniqid('', true)) . '.' . $ext;
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return '';
        }

        return trim($relativeDir, '/') . '/' . $filename;
    }
}
