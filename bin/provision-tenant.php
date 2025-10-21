#!/usr/bin/env php
<?php
// Lightweight provision script to register a new tenant.
// Usage (Linux/macOS):
//   php bin/provision-tenant.php --host=cliente.seuapp.com --db_host=localhost --db_name=ea_cliente --db_user=ea_cliente --db_pass=secret
// Then install the schema for that tenant:
//   TENANT_HOST=cliente.seuapp.com php index.php console install
// On Windows PowerShell:
//   $env:TENANT_HOST="cliente.seuapp.com"; php index.php console install

// Resolve repo root
$root = __DIR__ . '/..';
require_once $root . '/application/helpers/tenant_helper.php';

// Simple argv parser
$args = [];
foreach ($argv as $a) {
    if (str_starts_with($a, '--')) {
        $eq = strpos($a, '=');
        if ($eq !== false) {
            $key = substr($a, 2, $eq - 2);
            $val = substr($a, $eq + 1);
            $args[$key] = $val;
        } else {
            $args[substr($a, 2)] = true;
        }
    }
}

$host = strtolower(trim($args['host'] ?? ''));
if ($host === '') {
    fwrite(STDERR, "Missing --host=...\n");
    exit(1);
}

$entry = [
    'db_host' => $args['db_host'] ?? 'localhost',
    'db_name' => $args['db_name'] ?? '',
    'db_user' => $args['db_user'] ?? '',
    'db_pass' => $args['db_pass'] ?? '',
    'db_prefix' => $args['db_prefix'] ?? 'ea_',
];

if ($entry['db_name'] === '' || $entry['db_user'] === '') {
    fwrite(STDERR, "Missing --db_name and/or --db_user\n");
    exit(1);
}

$registry = tenant_registry();
$registry[$host] = $entry;

tenant_registry_write($registry);

echo "Registered tenant: {$host}\n";
echo "DB Host: {$entry['db_host']}\n";
echo "DB Name: {$entry['db_name']}\n";
echo "DB User: {$entry['db_user']}\n";
echo "\nNext step: run installation for this tenant.\n";
echo "Linux/macOS: TENANT_HOST={$host} php index.php console install\n";
echo "Windows PowerShell: $" . "env:TENANT_HOST=\"{$host}\"; php index.php console install\n";

