<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class OfficeDocumentConverter
{
    /** @var array<string, true> */
    private array $ownedDirectories = [];

    /** @return array{path: string, directory: string} */
    public function toPdf(string $sourcePath): array
    {
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (! in_array($extension, ['doc', 'docx', 'odt', 'rtf'], true)) {
            throw new RuntimeException('Choose a supported Word document for conversion.');
        }
        $job = $this->prepare($sourcePath, $extension);
        try {
            $profile = '<?xml version="1.0" encoding="UTF-8"?><oor:items xmlns:oor="http://openoffice.org/2001/registry">'
                .'<item oor:path="/org.openoffice.Office.Common/Security/Scripting"><prop oor:name="MacroSecurityLevel" oor:op="fuse"><value>3</value></prop><prop oor:name="DisableMacrosExecution" oor:op="fuse"><value>true</value></prop></item>'
                .'<item oor:path="/org.openoffice.Office.Writer/Content/Update"><prop oor:name="Link" oor:op="fuse"><value>0</value></prop><prop oor:name="Field" oor:op="fuse"><value>false</value></prop><prop oor:name="Chart" oor:op="fuse"><value>false</value></prop></item>'
                .'</oor:items>';
            $profilePath = $job['directory'].'/work/profile';
            $this->makeDirectory($profilePath);
            if (file_put_contents($profilePath.'/registrymodifications.xcu', $profile) === false) {
                throw new RuntimeException('The document converter could not prepare its private workspace.');
            }
            $this->secureFile($profilePath.'/registrymodifications.xcu');
            $this->run($job['directory'], [
                'libreoffice', '-env:UserInstallation=file:///work/profile',
                '--headless', '--invisible', '--nologo', '--nodefault', '--nofirststartwizard', '--norestore',
                '--convert-to', 'pdf:writer_pdf_Export', '--outdir', '/work/output', '/source/document.'.$extension,
            ]);
            $path = $job['directory'].'/work/output/document.pdf';
            if (! is_file($path) || filesize($path) === 0 || file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
                throw new RuntimeException('The converter could not read this Word document. Open it in Word and save a valid DOCX or PDF, then try again.');
            }
            return ['path' => $path, 'directory' => $job['directory']];
        } catch (Throwable $error) {
            $this->release($job);
            throw $error;
        }
    }

    public function release(array $result): void
    {
        $directory = $result['directory'] ?? null;
        if (! is_string($directory) || ! isset($this->ownedDirectories[$directory])) {
            return;
        }
        // Never follow links and never clean a caller-supplied or shared directory.
        $this->removeDirectory($directory);
        unset($this->ownedDirectories[$directory]);
    }

    public function pdfText(string $sourcePath): string
    {
        $job = $this->prepare($sourcePath, 'pdf');
        try {
            $this->run($job['directory'], ['pdftotext', '-layout', '-enc', 'UTF-8', '/source/document.pdf', '/work/output/document.txt']);
            $path = $job['directory'].'/work/output/document.txt';
            if (! is_file($path) || filesize($path) > 64 * 1024 * 1024) {
                throw new RuntimeException('The PDF text could not be read safely. Upload a readable text PDF or DOCX.');
            }
            $text = file_get_contents($path);
            if ($text === false) {
                throw new RuntimeException('The PDF text could not be read. Upload a readable text PDF or DOCX.');
            }
            return $text;
        } finally {
            $this->release($job);
        }
    }

    private function prepare(string $sourcePath, string $extension): array
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath) || filesize($sourcePath) === 0 || filesize($sourcePath) > 20 * 1024 * 1024) {
            throw new RuntimeException('Choose a non-empty document no larger than 20 MB.');
        }
        $root = storage_path('app/private/office-document-converter');
        if (! is_dir($root) && ! mkdir($root, 0700, true) && ! is_dir($root)) {
            throw new RuntimeException('The document converter could not create its private workspace.');
        }
        $directory = $root.'/'.bin2hex(random_bytes(20));
        $this->ownedDirectories[$directory] = true;
        $job = ['directory' => $directory];
        try {
            $this->makeDirectory($directory);
            $this->makeDirectory($directory.'/source');
            $this->makeDirectory($directory.'/work');
            $this->makeDirectory($directory.'/work/output');
            if (! copy($sourcePath, $directory.'/source/document.'.$extension)) {
                throw new RuntimeException('The document converter could not copy the document into its private workspace.');
            }
            $this->secureFile($directory.'/source/document.'.$extension);
            return $job;
        } catch (Throwable $error) {
            $this->release($job);
            throw $error;
        }
    }

    private function identity(): array
    {
        $uid = function_exists('posix_geteuid') ? posix_geteuid() : 0;
        $gid = function_exists('posix_getegid') ? posix_getegid() : 0;
        return [
            $uid > 0 ? $uid : max(1, (int) config('office-documents.uid', 1000)),
            $uid > 0 ? $gid : max(1, (int) config('office-documents.gid', 1000)),
        ];
    }

    private function makeDirectory(string $path): void
    {
        if (! mkdir($path, 0700)) {
            throw new RuntimeException('The document converter could not prepare its private workspace.');
        }
        $this->secureFile($path, 0700);
    }

    private function secureFile(string $path, int $mode = 0600): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            [$uid, $gid] = $this->identity();
            if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
                if (! chown($path, $uid) || ! chgrp($path, $gid)) {
                    throw new RuntimeException('The document converter could not secure its private workspace.');
                }
            }
            if (! chmod($path, $mode)) {
                throw new RuntimeException('The document converter could not secure its private workspace.');
            }
        }
    }

    private function run(string $directory, array $command): void
    {
        [$uid, $gid] = $this->identity();
        $name = 'orgchain-document-'.bin2hex(random_bytes(12));
        $timeout = max(5, min(120, (int) config('office-documents.timeout', 60)));
        $docker = (string) config('office-documents.docker_binary', 'docker');
        $arguments = [
            $docker, 'run', '--rm', '--name', $name, '--network', 'none', '--read-only',
            '--cap-drop', 'ALL', '--security-opt', 'no-new-privileges:true',
            '--memory', (string) config('office-documents.memory', '512m'), '--memory-swap', (string) config('office-documents.memory', '512m'),
            '--cpus', (string) config('office-documents.cpus', '1.0'), '--pids-limit', (string) config('office-documents.pids', 64),
            '--user', $uid.':'.$gid, '--env', 'HOME=/work', '--env', 'SAL_DISABLE_OPENCL=1',
            '--tmpfs', '/tmp:rw,noexec,nosuid,nodev,size=128m,uid='.$uid.',gid='.$gid.',mode=700',
            '--mount', 'type=bind,source='.realpath($directory.'/source').',target=/source,readonly',
            '--mount', 'type=bind,source='.realpath($directory.'/work').',target=/work',
            (string) config('office-documents.image', 'orgchain/document-converter:local'),
            'timeout', '--signal=KILL', $timeout.'s', ...$command,
        ];
        $process = new Process($arguments);
        $process->setTimeout($timeout + 10);
        $process->disableOutput();
        try {
            $process->mustRun();
        } catch (Throwable) {
            throw new RuntimeException('Document conversion failed. Ask the administrator to check the isolated document-converter image and Docker service, or save this document as DOCX/PDF and try again.');
        } finally {
            // A killed Docker client does not necessarily stop its container.
            try {
                $cleanup = new Process([$docker, 'rm', '--force', $name]);
                $cleanup->setTimeout(10);
                $cleanup->disableOutput();
                $cleanup->run();
            } catch (Throwable) {
                // Do not expose Docker output or private host paths to the caller.
            }
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (is_link($directory)) {
            unlink($directory);
            return;
        }
        if (! is_dir($directory)) {
            return;
        }
        foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry->isDir() && ! $entry->isLink()) {
                $this->removeDirectory($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($directory);
    }
}
