<?php defined('BASEPATH') or exit('No direct script access allowed');

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Signup extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('tenant');
        $this->load->helper('url');
        $this->load->helper('tenant_directory');
        $this->load->library('tenants_registry');
    }

    public function index(): void
    {
        html_vars(['page_title' => 'Signup']);
        $this->load->view('pages/signup');
    }

    public function store(): void
    {
        try {
            $allowedBaseDomain = getenv('ALLOWED_SIGNUP_BASE_DOMAIN')
                ?: (defined('Config::ALLOWED_SIGNUP_BASE_DOMAIN') ? Config::ALLOWED_SIGNUP_BASE_DOMAIN : '');

            $subdomain = trim((string) request('subdomain', ''));
            $hostInput = trim((string) request('host'));
            $companyName = trim((string) request('company_name'));
            $adminEmail = trim((string) request('admin_email'));
            $adminUsername = trim((string) request('admin_username'));
            $adminPassword = (string) request('admin_password');
            $adminPasswordConfirm = (string) request('admin_password_confirm');

            if (!$hostInput && $allowedBaseDomain) {
                $subdomain = strtolower($subdomain);
                if (!preg_match('/^[a-z0-9]([a-z0-9-]{1,30}[a-z0-9])?$/i', $subdomain)) {
                    throw new InvalidArgumentException('Invalid subdomain. Use letters, numbers or hyphen.');
                }

                $hostInput = $subdomain . '.' . ltrim($allowedBaseDomain, '.');
            }

            if (!$hostInput || !$companyName || !$adminEmail || !$adminUsername || !$adminPassword) {
                throw new InvalidArgumentException('All fields are required.');
            }

            if ($adminPassword !== $adminPasswordConfirm) {
                throw new InvalidArgumentException('Passwords do not match.');
            }

            $hostInput = strtolower($hostInput);
            if (!preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $hostInput)) {
                throw new InvalidArgumentException('Invalid host/domain.');
            }

            if ($allowedBaseDomain) {
                $base = '.' . ltrim($allowedBaseDomain, '.');
                if (!str_ends_with($hostInput, $base)) {
                    throw new InvalidArgumentException('Host not allowed.');
                }
            }

            $slug = $subdomain ?: explode('.', $hostInput)[0];
            $slug = strtolower(preg_replace('/[^a-z0-9-]+/i', '', $slug));
            if (!$slug) {
                throw new InvalidArgumentException('Could not determine client slug.');
            }

            $rcSecret = getenv('RECAPTCHA_SECRET') ?: (defined('Config::RECAPTCHA_SECRET') ? Config::RECAPTCHA_SECRET : '');
            $rcSiteKey = getenv('RECAPTCHA_SITE_KEY') ?: (defined('Config::RECAPTCHA_SITE_KEY') ? Config::RECAPTCHA_SITE_KEY : '');
            if ($rcSecret && $rcSiteKey) {
                $captcha = (string) request('g-recaptcha-response');
                if (!$captcha || !$this->verify_recaptcha($captcha)) {
                    throw new InvalidArgumentException('reCAPTCHA validation failed.');
                }
            }

            if (!$this->tenants_registry->is_enabled()) {
                throw new RuntimeException('Tenant meta database is not configured.');
            }

            $this->tenants_registry->ensure_schema();

            if ($this->tenants_registry->get_by_slug($slug) || $this->tenants_registry->get_by_host($hostInput)) {
                throw new InvalidArgumentException('This client is already registered.');
            }

            $dbHostProvision = getenv('PROVISION_DB_HOST') ?: (defined('Config::PROVISION_DB_HOST') ? Config::PROVISION_DB_HOST : Config::DB_HOST);
            $dbAdminUser = getenv('PROVISION_DB_USERNAME') ?: (defined('Config::PROVISION_DB_USERNAME') ? Config::PROVISION_DB_USERNAME : Config::DB_USERNAME);
            $dbAdminPass = getenv('PROVISION_DB_PASSWORD') ?: (defined('Config::PROVISION_DB_PASSWORD') ? Config::PROVISION_DB_PASSWORD : Config::DB_PASSWORD);

            $mysqli = @new mysqli($dbHostProvision, $dbAdminUser, $dbAdminPass);
            if ($mysqli->connect_errno) {
                throw new RuntimeException('Could not connect to provisioning MySQL: ' . $mysqli->connect_error);
            }

            $safeSlug = preg_replace('/[^a-z0-9_]+/i', '_', $slug);
            $dbName = substr((getenv('DB_NAME_PREFIX') ?: (defined('Config::DB_NAME_PREFIX') ? Config::DB_NAME_PREFIX : 'ea_')) . $safeSlug, 0, 64);
            $dbUser = substr((getenv('DB_USER_PREFIX') ?: (defined('Config::DB_USER_PREFIX') ? Config::DB_USER_PREFIX : 'ea_')) . $safeSlug, 0, 32);
            $dbPass = bin2hex(random_bytes(12));

            $dbNameEsc = $mysqli->real_escape_string($dbName);
            $dbUserEsc = $mysqli->real_escape_string($dbUser);
            $dbPassEsc = $mysqli->real_escape_string($dbPass);

            $sql = [
                "CREATE DATABASE IF NOT EXISTS `{$dbNameEsc}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                "CREATE USER IF NOT EXISTS '{$dbUserEsc}'@'%' IDENTIFIED BY '{$dbPassEsc}'",
                "ALTER USER '{$dbUserEsc}'@'%' IDENTIFIED BY '{$dbPassEsc}'",
                "GRANT ALL PRIVILEGES ON `{$dbNameEsc}`.* TO '{$dbUserEsc}'@'%'",
                'FLUSH PRIVILEGES',
            ];

            foreach ($sql as $stmt) {
                if (!$mysqli->query($stmt)) {
                    if (str_contains($stmt, 'CREATE USER') && $mysqli->errno === 1396) {
                        continue;
                    }
                    $err = $mysqli->error . ' (errno ' . $mysqli->errno . ')';
                    $mysqli->close();
                    throw new RuntimeException('Database provisioning error: ' . $err);
                }
            }
            $mysqli->close();

            $entry = [
                'db_host' => $dbHostProvision,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
                'db_prefix' => 'ea_',
                'slug' => $slug,
                'host' => $hostInput,
            ];

            $this->tenants_registry->upsert($entry);

            $row = $this->tenants_registry->get_by_slug($slug) ?: $this->tenants_registry->get_by_host($hostInput);
            if (!$row) {
                throw new RuntimeException('Failed to persist tenant meta information.');
            }

            $phpCandidates = [getenv('PHP_CLI') ?: null, PHP_BINARY ?: null, '/usr/local/bin/php', '/usr/bin/php', 'php'];
            $php = 'php';
            foreach ($phpCandidates as $candidate) {
                if (!$candidate) { continue; }
                if ($candidate === 'php') { $php = 'php'; break; }
                if (@is_executable($candidate) && !str_contains($candidate, 'php-fpm')) { $php = $candidate; break; }
                if ($php === 'php') { $php = $candidate; }
            }

            $cmd = escapeshellcmd($php) . ' ' . escapeshellarg(FCPATH . 'index.php') . ' console install';
            log_message('debug', 'Provision command: ' . $cmd);

            $descriptor = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $env = array_merge($_ENV, [
                'TENANT_HOST' => $hostInput,
                'TENANT_SLUG' => $slug,
                'EA_ADMIN_EMAIL' => $adminEmail,
                'EA_ADMIN_USERNAME' => $adminUsername,
                'EA_ADMIN_PASSWORD' => $adminPassword,
                'EA_COMPANY_NAME' => $companyName,
                'EA_COMPANY_EMAIL' => $adminEmail,
                'EA_COMPANY_LINK' => 'https://' . $hostInput,
            ]);

            $process = proc_open($cmd, $descriptor, $pipes, FCPATH, $env);
            if (!is_resource($process)) {
                throw new RuntimeException('Could not start provisioning process.');
            }

            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $errorOutput = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            log_message('debug', 'Provision stdout: ' . trim($output));
            log_message('debug', 'Provision stderr: ' . trim($errorOutput));

            if ($exitCode !== 0) {
                log_message('error', 'Provisioning failed for ' . $hostInput . ' code=' . $exitCode . ' stderr=' . $errorOutput);
                throw new RuntimeException('Provisioning failed, please contact support.');
            }

            tenant_directory_set($adminEmail, $slug);
            $this->send_welcome_email($adminEmail, $hostInput, $adminUsername, $companyName);

            html_vars([
                'page_title' => 'Signup Completed',
                'host' => $hostInput,
                'slug' => $slug,
                'admin_username' => $adminUsername,
                'admin_email' => $adminEmail,
            ]);

            $this->load->view('pages/signup_success');
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function verify_recaptcha(string $token): bool
    {
        $secret = getenv('RECAPTCHA_SECRET') ?: (defined('Config::RECAPTCHA_SECRET') ? Config::RECAPTCHA_SECRET : '');
        if (!$secret) {
            return true;
        }

        $post = http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $this->input->ip_address(),
        ]);

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $response = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            curl_close($ch);
        }

        if ($response === false) {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => $post,
                    'timeout' => 10,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
        }

        if ($response === false) {
            log_message('error', 'reCAPTCHA request failed');
            return false;
        }

        $data = json_decode($response, true);
        return is_array($data) && !empty($data['success']);
    }

    private function send_welcome_email(string $to, string $host, string $adminUsername, string $companyName): void
    {
        try {
            $subject = 'Bem-vindo ao Easy!Appointments';
            $html = $this->load->view(
                'emails/welcome_tenant_email',
                [
                    'subject' => $subject,
                    'host' => $host,
                    'admin_username' => $adminUsername,
                    'company_name' => $companyName,
                ],
                true
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

            $fromName = config('from_name') ?: $companyName;
            $domain = parse_url('https://' . $host, PHP_URL_HOST) ?: $host;
            $computedFrom = 'no-reply@' . preg_replace('/^www\./', '', $domain);
            $cfgFrom = (string) config('from_address');
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
        } catch (Throwable $e) {
            log_message('error', 'Welcome email unexpected error: ' . $e->getMessage());
        }
    }
}
