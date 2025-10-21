#!/usr/bin/env php
<?php
// Migrate all registered tenants to the latest schema.

$root = __DIR__ . '/..';
require_once $root . '/application/helpers/tenant_helper.php';

$registry = tenant_registry();
if (!$registry) {
    echo "No tenants found in tenants/registry.php\n";
    exit(0);
}

$php = PHP_BINARY ?: 'php';
$exitCode = 0;

foreach (array_keys($registry) as $host) {
    echo "\n==> Migrating tenant: {$host}\n";
    // Set env var for child process
    putenv('TENANT_HOST=' . $host);
    // Run migrate command
    $cmd = escapeshellcmd($php) . ' ' . escapeshellarg($root . '/index.php') . ' console migrate';
    passthru($cmd, $code);
    if ($code !== 0) {
        fwrite(STDERR, "Migration failed for {$host} with exit code {$code}\n");
        $exitCode = 1;
    }
}

exit($exitCode);

