<?php

namespace App\VotingSystem\Controllers;

use App\VotingSystem\Core\Auth;
use App\VotingSystem\Models\Candidate;

class MediaController
{
    public function candidate(): void
    {
        if (!$this->canViewCandidateMedia()) {
            http_response_code(403);
            exit('Forbidden');
        }

        $candidateId = (int) ($_GET['id'] ?? 0);

        if ($candidateId <= 0) {
            $this->redirectToPlaceholder();
        }

        $candidate = (new Candidate())->find($candidateId);
        $blob = $candidate['image_blob'] ?? null;
        $mime = trim((string) ($candidate['image_mime'] ?? ''));
        $allowed = ['image/jpeg', 'image/png'];

        if (is_resource($blob)) {
            $blob = stream_get_contents($blob);
        }

        if (is_string($blob) && $blob !== '' && in_array($mime, $allowed, true)) {
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . (string) strlen($blob));
            header('Cache-Control: private, max-age=300');
            echo $blob;
            exit;
        }

        $path = trim((string) ($candidate['image_path'] ?? ''));

        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\')) {
            $this->redirectToPlaceholder();
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            $this->redirectToPlaceholder();
        }

        $fullPath = $this->safeCandidateImagePath(voting_storage_path('uploads'), $path);

        // Backward compatibility for older records stored under public assets.
        if ($fullPath === null && str_starts_with($path, 'img/uploads/candidates/')) {
            $fullPath = $this->safeCandidateImagePath(voting_public_assets_path(), $path);
        }

        if ($fullPath === null || !is_file($fullPath) || !is_readable($fullPath)) {
            $this->redirectToPlaceholder();
        }

        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';

        if (!in_array($mime, $allowed, true)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($fullPath));
        header('Cache-Control: private, max-age=300');
        readfile($fullPath);
        exit;
    }

    private function canViewCandidateMedia(): bool
    {
        if (!empty($_SESSION['verified_voter_id'])) {
            return true;
        }

        return Auth::user() !== null;
    }

    private function safeCandidateImagePath(string $root, string $path): ?string
    {
        $rootPath = realpath($root);

        if ($rootPath === false) {
            return null;
        }

        $candidatePath = realpath($rootPath . DIRECTORY_SEPARATOR . ltrim($path, '/'));

        if ($candidatePath === false) {
            return null;
        }

        $rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
        $candidatePath = str_replace('\\', '/', $candidatePath);

        if ($candidatePath !== $rootPath && !str_starts_with($candidatePath, $rootPath . '/')) {
            return null;
        }

        return $candidatePath;
    }

    private function redirectToPlaceholder(): never
    {
        header('Location: ' . voting_asset('img/candidate-placeholder.svg'));
        exit;
    }
}
