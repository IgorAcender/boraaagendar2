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
        $primary = tenant_registry_primary_path();
        // If primary exists and is writable (or parent dir is writable), use it, else fallback under storage/
        $primaryDir = dirname($primary);
        if ((file_exists($primary) && is_writable($primary)) || (!file_exists($primary) && is_dir($primaryDir) && is_writable($primaryDir))) {
            return $primary;
        }

        return tenant_registry_fallback_path();
    }
}

if (!function_exists('tenant_registry')) {
    function tenant_registry(): array
    {
        $primary = tenant_registry_primary_path();
        $fallback = tenant_registry_fallback_path();

        $dataPrimary = null;
        $dataFallback = null;

        if (file_exists($primary)) {
            $dataPrimary = include $primary;
        }
        if (file_exists($fallback)) {
            $dataFallback = include $fallback;
        }

        // Prefer the non-empty registry; fallback to any array
        if (is_array($dataFallback) && (!is_array($dataPrimary) || empty($dataPrimary))) {
            return $dataFallback;
        }
        if (is_array($dataPrimary)) {
            return $dataPrimary;
        }
        if (is_array($dataFallback)) {
            return $dataFallback;
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

        // Helper to normalize different registry shapes to DB config
        $toDbCfg = static function (array $entry): ?array {
            $cfg = $entry;
            // Support nested shape: ['db' => [...], 'aliases' => [...]]
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
                'db_debug' => true,
                'cache_on' => false,
                'cachedir' => '',
                'char_set' => 'utf8mb4',
                'dbcollat' => 'utf8mb4_unicode_ci',
                'swap_pre' => '',
                'autoinit' => true,
                'stricton' => false,
            ];
        };

        // 1) Exact match
        if (isset($registry[$hostKey]) && is_array($registry[$hostKey])) {
            $db = $toDbCfg($registry[$hostKey]);
            if ($db) { return $db; }
        }

        // 2) Alias match (if entries include ['aliases' => ['host1', 'host2']])
        foreach ($registry as $entry) {
            if (!is_array($entry)) { continue; }
            $aliases = $entry['aliases'] ?? [];
            if (!is_array($aliases)) { continue; }
            foreach ($aliases as $alias) {
                $aliasKey = strtolower(preg_replace('/:.+$/', '', (string)$alias));
                if ($aliasKey === $hostKey) {
                    $db = $toDbCfg($entry);
                    if ($db) { return $db; }
                }
            }
        }

        // 3) Fallback to default tenant host (env), to ease domain changes
        $fallbackHost = getenv('DEFAULT_TENANT_HOST') ?: getenv('TENANT_FALLBACK_HOST');
        if ($fallbackHost) {
            $fallbackKey = strtolower(preg_replace('/:.+$/', '', $fallbackHost));
            if (isset($registry[$fallbackKey]) && is_array($registry[$fallbackKey])) {
                $db = $toDbCfg($registry[$fallbackKey]);
                if ($db) { return $db; }
            }
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
            @mkdir($dir, 0775, true);
        }

        // Ensure writability
        if (file_exists($path) && !is_writable($path)) {
            @chmod($path, 0664);
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0775);
        }

        // Export as PHP file returning array
        $export = var_export($registry, true);
        $content = "<?php\nreturn " . $export . ";\n";
        $tmp = $path . '.tmp.' . uniqid('', true);
        $bytes = @file_put_contents($tmp, $content, LOCK_EX);
        if ($bytes === false) {
            // Fallback to direct write
            if (@file_put_contents($path, $content) === false) {
                throw new RuntimeException('Cannot write tenant registry at: ' . $path);
            }
            return;
        }
        if (!@rename($tmp, $path)) {
            // Fallback to direct write
            if (@file_put_contents($path, $content) === false) {
                throw new RuntimeException('Cannot finalize tenant registry write at: ' . $path);
            }
        }
    }
}
