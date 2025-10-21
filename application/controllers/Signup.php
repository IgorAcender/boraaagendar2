<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * ---------------------------------------------------------------------------- */

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Signup controller.
 *
 * Simple self-service provisioning page that registers a tenant and triggers
 * an installation for that tenant in a child PHP process.
 */
class Signup extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('tenant');
        $this->load->helper('url');
        $this->load->helper('tenant_directory');
    }

    /**
     * Show the signup page.
     */
    public function index(): void
    {
        html_vars([
            'page_title' => 'Signup',
        ]);

        $this->load->view('pages/signup');
    }

    /**
     * Handle signup form submission.
     */
    public function store(): void
    {
        try {
            $allowedBaseDomain = getenv('ALLOWED_SIGNUP_BASE_DOMAIN') ?: (defined('Config::ALLOWED_SIGNUP_BASE_DOMAIN') ? Config::ALLOWED_SIGNUP_BASE_DOMAIN : '');

            $subdomain = trim((string) request('subdomain', ''));
            $host = trim((string) request('host'));
            $company_name = trim((string) request('company_name'));
            $admin_email = trim((string) request('admin_email'));
            $admin_username = trim((string) request('admin_username'));
            $admin_password = (string) request('admin_password');
            $admin_password_confirm = (string) request('admin_password_confirm');

            // If base domain is configured and subdomain provided, build host from it.
            if (!$host && $allowedBaseDomain && $subdomain) {
                // Validate subdomain (basic)
                if (!preg_match('/^[a-z0-9]([a-z0-9-]{1,30}[a-z0-9])?$/i', $subdomain)) {
                    throw new InvalidArgumentException('Subdomínio inválido. Use letras, números e hífen.');
                }

                $host = strtolower($subdomain) . '.' . ltrim($allowedBaseDomain, '.');
            }

            if (!$host || !$company_name || !$admin_email || !$admin_username || !$admin_password) {
                throw new InvalidArgumentException('Missing required fields.');
            }

            if ($admin_password !== $admin_password_confirm) {
                throw new InvalidArgumentException('As senhas não conferem.');
            }

            // reCAPTCHA validation (if configured)
            $rcSiteKey = getenv('RECAPTCHA_SITE_KEY') ?: (defined('Config::RECAPTCHA_SITE_KEY') ? Config::RECAPTCHA_SITE_KEY : '');
            $rcSecret  = getenv('RECAPTCHA_SECRET') ?: (defined('Config::RECAPTCHA_SECRET') ? Config::RECAPTCHA_SECRET : '');
            if (!empty($rcSecret) && !empty($rcSiteKey)) {
                $captcha = (string) request('g-recaptcha-response');
                if (!$captcha) {
                    throw new InvalidArgumentException('Valide o reCAPTCHA para continuar.');
                }

                if (!$this->verify_recaptcha($captcha)) {
                    throw new InvalidArgumentException('Falha na validação do reCAPTCHA.');
                }
            }

            // Basic host validation
            $host = strtolower($host);
            if (!preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $host)) {
                throw new InvalidArgumentException('Invalid host. Use a full domain like cliente.seuapp.com');
            }

            if ($allowedBaseDomain) {
                $base = '.' . ltrim($allowedBaseDomain, '.');
                if (!str_ends_with($host, $base)) {
                    throw new InvalidArgumentException('Host not allowed. Must be a subdomain of ' . $allowedBaseDomain);
                }
            }

            // Prevent duplicates
            $registry = tenant_registry();
            if (isset($registry[$host])) {
                throw new InvalidArgumentException('This host is already registered.');
            }

            // Auto-create database & user on the configured DB server
            $dbHost   = getenv('PROVISION_DB_HOST') ?: (defined('Config::PROVISION_DB_HOST') ? Config::PROVISION_DB_HOST : Config::DB_HOST);
            $adminUser = getenv('PROVISION_DB_USERNAME') ?: (defined('Config::PROVISION_DB_USERNAME') ? Config::PROVISION_DB_USERNAME : Config::DB_USERNAME);
            $adminPass = getenv('PROVISION_DB_PASSWORD') ?: (defined('Config::PROVISION_DB_PASSWORD') ? Config::PROVISION_DB_PASSWORD : Config::DB_PASSWORD);

            // Derive safe identifiers primarily from slug (subdomain)
            $hostKey = strtolower(preg_replace('/:.+$/', '', $host));
            $slug = $subdomain ?: explode('.', $hostKey)[0];
            $safe = preg_replace('/[^a-z0-9_]+/i', '_', $slug);
            $namePrefix = getenv('DB_NAME_PREFIX') ?: (defined('Config::DB_NAME_PREFIX') ? Config::DB_NAME_PREFIX : 'ea_');
            $userPrefix = getenv('DB_USER_PREFIX') ?: (defined('Config::DB_USER_PREFIX') ? Config::DB_USER_PREFIX : 'ea_');
            $dbName = substr($namePrefix . $safe, 0, 64);
            $dbUser = substr($userPrefix . $safe, 0, 32);
            $dbPass = bin2hex(random_bytes(12)); // 24 chars

            $mysqli = @new mysqli($dbHost, $adminUser, $adminPass);
            if ($mysqli->connect_errno) {
                throw new RuntimeException('DB admin connection failed: ' . $mysqli->connect_error);
            }

            $dbNameEsc = $mysqli->real_escape_string($dbName);
            $dbUserEsc = $mysqli->real_escape_string($dbUser);
            $dbPassEsc = $mysqli->real_escape_string($dbPass);

            $sql = [];
            $sql[] = "CREATE DATABASE IF NOT EXISTS `{$dbNameEsc}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $sql[] = "CREATE USER IF NOT EXISTS '{$dbUserEsc}'@'%' IDENTIFIED BY '{$dbPassEsc}'";
            $sql[] = "ALTER USER '{$dbUserEsc}'@'%' IDENTIFIED BY '{$dbPassEsc}'"; // ensure password
            $sql[] = "GRANT ALL PRIVILEGES ON `{$dbNameEsc}`.* TO '{$dbUserEsc}'@'%'";
            $sql[] = "FLUSH PRIVILEGES";

            foreach ($sql as $stmt) {
                if (!$mysqli->query($stmt)) {
                    // Ignore duplicate user error on CREATE USER
                    if (str_starts_with($stmt, 'CREATE USER') && $mysqli->errno === 1396) {
                        continue;
                    }
                    $err = $mysqli->error . ' (errno ' . $mysqli->errno . ')';
                    $mysqli->close();
                    throw new RuntimeException('Provision SQL failed: ' . $err);
                }
            }
            $mysqli->close();

            // Update tenants registry with created credentials (store by host and by slug)
            $entry = [
                'db_host' => $dbHost,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
                'db_prefix' => 'ea_',
                'slug' => $slug,
            ];
            $registry[$hostKey] = $entry;
            $registry[$slug] = $entry;
            tenant_registry_write($registry);

            // Verify that registry persisted (avoid running install on fallback DB)
            $check = tenant_registry();
            if (!isset($check[$hostKey]) && !isset($check[$slug])) {
                throw new RuntimeException('Falha ao gravar o registro de tenants (permissões de escrita).');
            }

            // Spawn console install for that tenant with admin/company overrides
            $root = FCPATH;
            // Prefer explicit PHP CLI binary; fallback to common locations or 'php'.
            $phpCandidates = [getenv('PHP_CLI') ?: null, '/usr/local/bin/php', '/usr/bin/php', 'php'];
            $php = 'php';
            foreach ($phpCandidates as $cand) {
                if (!$cand) { continue; }
                if ($cand === 'php') { $php = 'php'; break; }
                if (@is_executable($cand)) { $php = $cand; break; }
                // Still allow non-check paths; pick the first non-empty if nothing else works
                if ($php === 'php') { $php = $cand; }
            }
            $cmd = escapeshellcmd($php) . ' ' . escapeshellarg($root . 'index.php') . ' console install';

            $descriptorSpec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $env = array_merge($_ENV, [
                'TENANT_HOST' => $host,
                'TENANT_SLUG' => $slug,
                'EA_ADMIN_EMAIL' => $admin_email,
                'EA_ADMIN_USERNAME' => $admin_username,
                'EA_ADMIN_PASSWORD' => $admin_password,
                'EA_COMPANY_NAME' => $company_name,
                'EA_COMPANY_EMAIL' => $admin_email,
                'EA_COMPANY_LINK' => 'https://' . $host,
            ]);

            $process = proc_open($cmd, $descriptorSpec, $pipes, $root, $env);
            if (!is_resource($process)) {
                throw new RuntimeException('Could not start provisioning process.');
            }

            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $errorOutput = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if ($exitCode !== 0) {
                log_message('error', 'Provisioning failed for ' . $host . ' code=' . $exitCode . ' stderr=' . $errorOutput);
                throw new RuntimeException('Provisioning failed, please contact support.');
            }

            // Index admin email for global login routing
            tenant_directory_set($admin_email, $slug);

            // Send welcome email (best-effort)
            $this->send_welcome_email($admin_email, $host, $admin_username, $company_name);

            // Redirect to thank-you page (login instructions)
            html_vars([
                'page_title' => 'Signup Completed',
                'host' => $host,
                'slug' => $slug,
                'admin_username' => $admin_username,
                'admin_email' => $admin_email,
            ]);
            $this->load->view('pages/signup_success');
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function verify_recaptcha(string $token): bool
    {
        $secret = Config::RECAPTCHA_SECRET;
        if (empty($secret)) {
            return true; // Not configured
        }

        $post = http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $this->input->ip_address(),
        ]);

        $url = 'https://www.google.com/recaptcha/api/siteverify';

        // Prefer cURL
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $res = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            if ($res === false) {
                log_message('error', 'reCAPTCHA cURL error: ' . $err);
                return false;
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => $post,
                    'timeout' => 10,
                ],
            ]);
            $res = @file_get_contents($url, false, $context);
            if ($res === false) {
                log_message('error', 'reCAPTCHA HTTP error (file_get_contents)');
                return false;
            }
        }

        $data = json_decode($res, true);
        return is_array($data) && !empty($data['success']);
    }

    private function send_welcome_email(string $to, string $host, string $admin_username, string $company_name): void
    {
        try {
            $subject = 'Bem-vindo ao Easy!Appointments';

            $html = $this->load->view(
                'emails/welcome_tenant_email',
                [
                    'subject' => $subject,
                    'host' => $host,
                    'admin_username' => $admin_username,
                    'company_name' => $company_name,
                ],
                true,
            );

            $mailer = new PHPMailer(true);
            $mailer->CharSet = 'UTF-8';
            if (config('protocol') === 'smtp') {
                $mailer->isSMTP();
                $mailer->Host = config('smtp_host');
                $mailer->SMTPAuth = (bool) config('smtp_auth');
                $mailer->Username = config('smtp_user');
                $mailer->Password = config('smtp_pass');
                $mailer->SMTPSecure = config('smtp_crypto');
                $mailer->Port = (int) config('smtp_port');
            }

            $fromName = config('from_name') ?: $company_name;
            $domain = parse_url('https://' . $host, PHP_URL_HOST) ?: $host;
            $computedFrom = 'no-reply@' . preg_replace('/^www\./', '', $domain);
            $cfgFrom = (string) config('from_address');
            // Avoid invalid default 'no-reply@localhost'
            $fromAddress = (empty($cfgFrom) || str_contains($cfgFrom, 'localhost')) ? $computedFrom : $cfgFrom;
            if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
                $fromAddress = $computedFrom;
            }

            $mailer->setFrom($fromAddress, $fromName);
            $mailer->addReplyTo($fromAddress);
            if ($to) {
                $mailer->addAddress($to);
            }
            $mailer->Subject = $subject;
            $mailer->isHTML();
            $mailer->Body = $html;
            $mailer->AltBody = strip_tags($html);

            $mailer->send();
        } catch (MailException $e) {
            log_message('error', 'Welcome email failed: ' . $e->getMessage());
        } catch (\Throwable $e) {
            // Never block provisioning due to email issues
            log_message('error', 'Welcome email unexpected error: ' . $e->getMessage());
        }
    }
}
