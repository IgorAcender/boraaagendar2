<?php defined('BASEPATH') or exit('No direct script access allowed');

class Global_login extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('tenant');
        $this->load->helper('tenant_directory');
        $this->load->helper('password');
    }

    public function index(): void
    {
        html_vars(['page_title' => 'Entrar']);
        $this->load->view('pages/global_login');
    }

    public function auth(): void
    {
        try {
            $email = strtolower(trim((string) request('email')));
            $password = (string) request('password');

            if (!$email || !$password) {
                throw new InvalidArgumentException('Informe e-mail e senha.');
            }

            // 1) Find slug via directory
            $dir = tenant_directory_read();
            $slug = $dir[$email] ?? null;

            $registry = tenant_registry();

            // Helper: connect and try fetch user by email
            $tryDb = function(array $cfg) use ($email) {
                $db = $this->load->database([
                    'dsn' => '',
                    'hostname' => $cfg['db_host'],
                    'username' => $cfg['db_user'],
                    'password' => $cfg['db_pass'],
                    'database' => $cfg['db_name'],
                    'dbdriver' => 'mysqli',
                    'dbprefix' => $cfg['db_prefix'] ?? 'ea_',
                    'pconnect' => FALSE,
                    'db_debug' => (ENVIRONMENT !== 'production'),
                    'cache_on' => FALSE,
                    'cachedir' => '',
                    'char_set' => 'utf8mb4',
                    'dbcollat' => 'utf8mb4_unicode_ci',
                    'swap_pre' => '',
                    'autoinit' => TRUE,
                    'stricton' => FALSE,
                ], TRUE);

                $row = $db->select('users.id, users.email, users.timezone, users.language, user_settings.username, user_settings.password, user_settings.salt, roles.slug as role_slug')
                    ->from('users')
                    ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
                    ->join('roles', 'roles.id = users.id_roles', 'inner')
                    ->where('users.email', $email)
                    ->get()->row_array();
                return [$db, $row];
            };

            $dbCfg = null; $userRow = null; $dbConn = null; $foundSlug = $slug;

            if ($slug && isset($registry[$slug])) {
                $dbCfg = $registry[$slug];
                [$dbConn, $userRow] = $tryDb($dbCfg);
            }

            if (!$userRow) {
                // Fallback: scan registry (first hit wins)
                foreach ($registry as $key => $entry) {
                    if (!is_array($entry) || empty($entry['db_name'])) { continue; }
                    [$db, $row] = $tryDb($entry);
                    if ($row) { $dbConn = $db; $userRow = $row; $dbCfg = $entry; $foundSlug = $entry['slug'] ?? $key; break; }
                }
            }

            if (!$userRow) {
                throw new InvalidArgumentException('E-mail ou senha inválidos.');
            }

            // Verify password
            $hash = hash_password($userRow['salt'], $password);
            if (!hash_equals($userRow['password'], $hash)) {
                throw new InvalidArgumentException('E-mail ou senha inválidos.');
            }

            // Persist tenant slug and start session
            setcookie('TENANT_SLUG', strtolower((string)$foundSlug), time() + 60*60*24*30, '/');

            session([
                'user_id' => (int) $userRow['id'],
                'user_email' => $userRow['email'],
                'username' => $userRow['username'],
                'timezone' => $userRow['timezone'] ?: date_default_timezone_get(),
                'language' => $userRow['language'] ?: Config::LANGUAGE,
                'role_slug' => $userRow['role_slug'],
            ]);

            redirect('calendar');
        } catch (Throwable $e) {
            html_vars(['page_title' => 'Entrar', 'error' => $e->getMessage()]);
            $this->load->view('pages/global_login');
        }
    }
}

