<?php defined('BASEPATH') or exit('No direct script access allowed');

class Tenants_registry
{
    /** @var EA_Controller|CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function is_enabled(): bool
    {
        return (bool) (getenv('META_DB_HOST') && getenv('META_DB_NAME') && getenv('META_DB_USERNAME'));
    }

    protected function connection_config(): array
    {
        return [
            'dsn'      => '',
            'hostname' => getenv('META_DB_HOST'),
            'username' => getenv('META_DB_USERNAME'),
            'password' => getenv('META_DB_PASSWORD') ?: '',
            'database' => getenv('META_DB_NAME'),
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => FALSE,
            'db_debug' => (ENVIRONMENT !== 'production'),
            'cache_on' => FALSE,
            'cachedir' => '',
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_unicode_ci',
            'swap_pre' => '',
            'autoinit' => TRUE,
            'stricton' => FALSE,
        ];
    }

    protected function db()
    {
        return $this->CI->load->database($this->connection_config(), TRUE);
    }

    public function ensure_schema(): void
    {
        $db = $this->db();
        $db->query('CREATE TABLE IF NOT EXISTS tenants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(190) NOT NULL UNIQUE,
            host VARCHAR(255) NULL UNIQUE,
            db_host VARCHAR(255) NOT NULL,
            db_name VARCHAR(255) NOT NULL,
            db_user VARCHAR(255) NOT NULL,
            db_pass VARCHAR(255) NOT NULL,
            db_prefix VARCHAR(32) NOT NULL DEFAULT "ea_",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function get_by_host(string $host): ?array
    {
        if (!$this->is_enabled()) { return null; }
        $row = $this->db()->get_where('tenants', ['host' => $host])->row_array();
        return $row ?: null;
    }

    public function get_by_slug(string $slug): ?array
    {
        if (!$this->is_enabled()) { return null; }
        $row = $this->db()->get_where('tenants', ['slug' => $slug])->row_array();
        return $row ?: null;
    }

    public function upsert(array $entry): void
    {
        if (!$this->is_enabled()) { return; }
        $this->ensure_schema();
        $db = $this->db();
        $exists = $db->get_where('tenants', ['slug' => $entry['slug']])->row_array();
        if ($exists) {
            $db->where('slug', $entry['slug'])->update('tenants', $entry);
            return;
        }
        $db->insert('tenants', $entry);
    }
}

