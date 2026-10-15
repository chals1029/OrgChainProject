<?php

return [
    'image' => env('OFFICE_DOCUMENT_CONVERTER_IMAGE', 'orgchain/document-converter:local'),
    'docker_binary' => env('OFFICE_DOCUMENT_DOCKER_BINARY', 'docker'),
    // Docker Desktop bind mounts support this non-root identity. Linux uses
    // the PHP process's non-root UID/GID so private bind files remain readable.
    'uid' => (int) env('OFFICE_DOCUMENT_CONVERTER_UID', 1000),
    'gid' => (int) env('OFFICE_DOCUMENT_CONVERTER_GID', 1000),
    'timeout' => 60,
    'memory' => '512m',
    'cpus' => '1.0',
    'pids' => 64,
];
