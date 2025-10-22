<?php
// Tenant helpers for host/slug resolution and DB configuration.

if (!function_exists('tenant_current_host')) {
    function tenant_current_host(): ?string
    {
        $host = getenv('TENANT_HOST');
        if ($host && is_string($host)) {
            return strtolower($host);
        }

        if (!empty($_SERVER['HTTP_HOST'])) {
            return strtolower($_SERVER['HTTP_HOST']);
        }

        return null;
    }
}

if (!function_exists('tenant_current_slug')) {
    function tenant_current_slug(): ?string
    {
        $slug = getenv('TENANT_SLUG');
        if ($slug && is_string($slug)) {
            return strtolower($slug);
        }

        if (!empty($_GET['t'])) {
            return strtolower(preg_replace('/[^a-z0-9-_.]+/i', '', (string) $_GET['t']));
        }

        if (!empty($_COOKIE['TENANT_SLUG'])) {
            return strtolower(preg_replace('/[^a-z0-9-_.]+/i', '', (string) $_COOKIE['TENANT_SLUG']));
        }

        return null;
    }
}

if (!function_exists('tenant_registry_primary_path')) {
    function tenant_registry_primary_path(): string
    {
        $base = defined('FCPATH') ? FCPATH : getcwd();
        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tenants' . DIRECTORY_SEPARATOR . 'registry.php';
    }
}

if (!function_exists('tenant_registry_fallback_path')) {
    function tenant_registry_fallback_path(): string
    {
        $base = defined('FCPATH') ? FCPATH : getcwd();
        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tenants' . DIRECTORY_SEPARATOR . 'registry.php';
    }
}

if (!function_exists('tenant_registry_path')) {
    function tenant_registry_path(): string
    {
        return tenant_registry_fallback_path();
    }
}

if (!function_exists('tenant_registry')) {
    function tenant_registry(): array
    {
        $primary = tenant_registry_primary_path();
        $fallback = tenant_registry_fallback_path();

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($primary, true);
            @opcache_invalidate($fallback, true);
        }

        $dataPrimary = file_exists($primary) ? include $primary : [];
        $dataFallback = file_exists($fallback) ? include $fallback : [];

        $dataPrimary = is_array($dataPrimary) ? $dataPrimary : [];
        $dataFallback = is_array($dataFallback) ? $dataFallback : [];

        return array_merge($dataPrimary, $dataFallback);
    }
}

if (!function_exists('tenant_db_config')) {
    function tenant_db_config(?string $host = null): ?array
    {
        $host = $host ?: tenant_current_host();
        $slug = tenant_current_slug();

        $registry = tenant_registry();
        $hostKey = $host ? strtolower(preg_replace('/:.+$/', '', $host)) : null;

        $toDbCfg = static function (array $entry): ?array {
            $cfg = $entry;
            if (isset($entry['db']) && is_array($entry['db'])) {
                $cfg = $entry['db'];
            }

            $dbHost = $cfg['db_host'] ?? 'localhost';
            $dbName = $cfg['db_name'] ?? null;
            $dbUser = $cfg['db_user'] ?? null;
            $dbPass = $cfg['db_pass'] ?? '';
            $dbPrefix = $cfg['db_prefix'] ?? 'ea_';

            if (!$dbName || !$dbUser) {
                return null;
            }

            return [
                'hostname' => $dbHost,
                'username' => $dbUser,
                'password' => $dbPass,
                'database' => $dbName,
                'dbdriver' => 'mysqli',
                'dbprefix' => $dbPrefix,
                'pconnect' => false,
                'db_debug' => (ENVIRONMENT !== 'production'),
                'cache_on' => false,
                'cachedir' => '',
                'char_set' => 'utf8mb4',
                'dbcollat' => 'utf8mb4_unicode_ci',
                'swap_pre' => '',
                'autoinit' => true,
                'stricton' => false,
            ];
        };

        if (getenv('META_DB_HOST') && getenv('META_DB_NAME') && getenv('META_DB_USERNAME')) {
            $CI = &get_instance();
            $CI->load->library('tenants_registry');
            /** @var Tenants_registry $tr */
            $tr = $CI->tenants_registry;
            if ($hostKey) {
                $row = $tr->get_by_host($hostKey);
                if ($row) {
                    return $toDbCfg($row);
                }
            }
            if ($slug) {
                $row = $tr->get_by_slug($slug);
                if ($row) {
                    return $toDbCfg($row);
                }
            }
        }

        if ($hostKey && isset($registry[$hostKey]) && is_array($registry[$hostKey])) {
            $db = $toDbCfg($registry[$hostKey]);
            if ($db) {
                return $db;
            }
        }

        if ($slug && isset($registry[$slug]) && is_array($registry[$slug])) {
            $db = $toDbCfg($registry[$slug]);
            if ($db) {
                return $db;
            }
        }

        foreach ($registry as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $aliases = $entry['aliases'] ?? [];
            if (!is_array($aliases)) {
                continue;
            }
            foreach ($aliases as $alias) {
                $aliasKey = strtolower(preg_replace('/:.+$/', '', (string) $alias));
                if ($aliasKey === $hostKey) {
                    $db = $toDbCfg($entry);
                    if ($db) {
                        return $db;
                    }
                }
            }
        }

        if ($slug) {
            foreach ($registry as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                if (($entry['slug'] ?? null) && strtolower((string) $entry['slug']) === $slug) {
                    $db = $toDbCfg($entry);
                    if ($db) {
                        return $db;
                    }
                }
            }
        }

        $fallbackHost = getenv('DEFAULT_TENANT_HOST') ?: getenv('TENANT_FALLBACK_HOST');
        if ($fallbackHost) {
            $fallbackKey = strtolower(preg_replace('/:.+$/', '', $fallbackHost));
            if (isset($registry[$fallbackKey]) && is_array($registry[$fallbackKey])) {
                $db = $toDbCfg($registry[$fallbackKey]);
                if ($db) {
                    return $db;
                }
            }
        }

        return null;
    }
}

if (!function_exists('tenant_registry_write')) {
    function tenant_registry_write(array $registry): void
    {
        $path = tenant_registry_fallback_path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (file_exists($path) && !is_writable($path)) {
            @chmod($path, 0664);
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0775);
        }

        $export = var_export($registry, true);
        $content = "<?php\nreturn " . $export . ";\n";
        $tmp = $path . '.tmp.' . uniqid('', true);
        $bytes = @file_put_contents($tmp, $content, LOCK_EX);
        if ($bytes === false) {
            if (@file_put_contents($path, $content) === false) {
                throw new RuntimeException('Cannot write tenant registry at: ' . $path);
            }
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($path, true);
            }
            return;
        }
        if (!@rename($tmp, $path)) {
            if (@file_put_contents($path, $content) === false) {
                throw new RuntimeException('Cannot finalize tenant registry write at: ' . $path);
            }
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }
    }
}
