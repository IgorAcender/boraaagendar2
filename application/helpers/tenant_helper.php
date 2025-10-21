<?php
// Minimal, framework-independent helper for tenant resolution and DB config.

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

if (!function_exists('tenant_registry_path')) {
    function tenant_registry_path(): string
    {
        // FCPATH is defined by index.php during bootstrap; fall back to cwd.
        $base = defined('FCPATH') ? FCPATH : getcwd();
        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tenants' . DIRECTORY_SEPARATOR . 'registry.php';
    }
}

if (!function_exists('tenant_registry')) {
    function tenant_registry(): array
    {
        $path = tenant_registry_path();
        if (!file_exists($path)) {
            return [];
        }

        $data = include $path;
        if (is_array($data)) {
            return $data;
        }

        return [];
    }
}

if (!function_exists('tenant_db_config')) {
    function tenant_db_config(?string $host = null): ?array
    {
        $host = $host ?: tenant_current_host();
        if (!$host) {
            return null;
        }

        $registry = tenant_registry();

        // Allow exact match and base-domain match (strip port).
        $hostKey = strtolower(preg_replace('/:.+$/', '', $host));

        if (isset($registry[$hostKey]) && is_array($registry[$hostKey])) {
            $cfg = $registry[$hostKey];
            // Normalize keys and provide sensible defaults.
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
                'db_debug' => true,
                'cache_on' => false,
                'cachedir' => '',
                'char_set' => 'utf8mb4',
                'dbcollat' => 'utf8mb4_unicode_ci',
                'swap_pre' => '',
                'autoinit' => true,
                'stricton' => false,
            ];
        }

        return null;
    }
}

// Utility for writing the registry from external scripts (not used by CI runtime directly).
if (!function_exists('tenant_registry_write')) {
    function tenant_registry_write(array $registry): void
    {
        $path = tenant_registry_path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Export as PHP file returning array
        $export = var_export($registry, true);
        $content = "<?php\nreturn " . $export . ";\n";
        $tmp = $path . '.tmp.' . uniqid('', true);
        file_put_contents($tmp, $content, LOCK_EX);
        // Atomic replace
        rename($tmp, $path);
    }
}
