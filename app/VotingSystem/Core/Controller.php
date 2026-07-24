<?php

namespace App\VotingSystem\Core;

class Controller
{
    public function view(string $view, array $data = [], string $layout = 'public'): void
    {
        extract($data, EXTR_SKIP);

        $viewsRoot = base_path('resources/views/voting-system');

        ob_start();
        require $viewsRoot.'/'.$view.'.php';
        $content = ob_get_clean();

        require $viewsRoot.'/layouts/'.$layout.'.php';
        unset($_SESSION['_old']);
    }

    protected function redirect(string $path): never
    {
        voting_redirect($path);
    }

    protected function request(): array
    {
        return $_POST;
    }

    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }

        $this->requireSameOriginPost();

        if (! hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) {
            http_response_code(419);
            exit('Invalid request token');
        }
    }

    private function requireSameOriginPost(): void
    {
        $source = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');

        if ($source === '') {
            $source = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        }

        if ($source === '') {
            return;
        }

        $sourceHost = \normalized_host((string) parse_url($source, PHP_URL_HOST));
        $currentHost = \request_host();

        if ($sourceHost === '') {
            return;
        }

        if ($sourceHost !== '' && $currentHost !== '' && hash_equals($currentHost, $sourceHost)) {
            return;
        }

        http_response_code(403);
        exit('Cross-site request blocked');
    }

    protected function rememberOldInput(): void
    {
        $_SESSION['_old'] = array_diff_key($_POST, array_flip([
            '_csrf',
            'password',
            'password_confirmation',
            'code',
            'auth_step',
            'choices',
        ]));
    }

    protected function requireAuth(?array $roles = null): array
    {
        $user = Auth::user();

        if ($user === null) {
            $path = voting_current_path();

            if (is_staff_protected_path($path)) {
                http_response_code(404);
                $this->view('errors/404', [], 'public');
                exit;
            }

            voting_flash('warning', 'Please sign in to continue.');
            $this->redirect(admin_login_path());
        }

        if ($roles !== null && ! in_array($user['role'], $roles, true)) {
            http_response_code(403);
            $this->view('errors/403', [], 'admin');
            exit;
        }

        if (! headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        return $user;
    }
}
