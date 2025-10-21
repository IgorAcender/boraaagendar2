<?php defined('BASEPATH') or exit('No direct script access allowed');

// Email configuration with environment overrides.
// Defaults target local development with Mailpit (see docker-compose.yml).
// In production, override via environment variables (see docker-compose.prod.yml).

// Helpers
$env = fn(string $key, $default = null) => getenv($key) !== false ? getenv($key) : $default;
$env_bool = fn($v, $default = false) => $v === null ? $default : in_array(strtolower((string)$v), ['1','true','yes','on'], true);
$env_int = fn($v, $default = 0) => $v === null ? $default : (int)$v;

// Defaults (DEV)
$config['useragent'] = 'Easy!Appointments';
$config['protocol'] = 'smtp';
$config['mailtype'] = 'html';
$config['smtp_debug'] = '0';
$config['smtp_auth'] = false; // Mailpit does not require auth
$config['smtp_host'] = 'mailpit';
$config['smtp_user'] = '';
$config['smtp_pass'] = '';
$config['smtp_crypto'] = '';
$config['smtp_port'] = 1025;
$config['from_name'] = 'Seu App (Dev)';
$config['from_address'] = 'no-reply@localhost';
$config['reply_to'] = 'no-reply@localhost';
$config['crlf'] = "\r\n";
$config['newline'] = "\r\n";

// Environment overrides (PROD)
$protocol = $env('SMTP_PROTOCOL');
if ($protocol) { $config['protocol'] = $protocol; }

$host = $env('SMTP_HOST');
if ($host) { $config['smtp_host'] = $host; }

$port = $env('SMTP_PORT');
if ($port !== null) { $config['smtp_port'] = $env_int($port, $config['smtp_port']); }

$auth = $env('SMTP_AUTH');
if ($auth !== null) { $config['smtp_auth'] = $env_bool($auth, $config['smtp_auth']); }

$user = $env('SMTP_USER');
if ($user) { $config['smtp_user'] = $user; }

$pass = $env('SMTP_PASS');
if ($pass) { $config['smtp_pass'] = $pass; }

$crypto = $env('SMTP_CRYPTO');
if ($crypto !== null) { $config['smtp_crypto'] = $crypto; }

$fromName = $env('SMTP_FROM_NAME');
if ($fromName) { $config['from_name'] = $fromName; }

$fromAddr = $env('SMTP_FROM_ADDRESS');
if ($fromAddr) { $config['from_address'] = $fromAddr; }

$replyTo = $env('SMTP_REPLY_TO');
if ($replyTo) { $config['reply_to'] = $replyTo; }

