<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('tenant_directory_path')) {
    function tenant_directory_path(): string
    {
        $base = defined('FCPATH') ? FCPATH : getcwd();
        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tenants' . DIRECTORY_SEPARATOR . 'directory.php';
    }
}

if (!function_exists('tenant_directory_read')) {
    function tenant_directory_read(): array
    {
        $path = tenant_directory_path();
        if (file_exists($path)) {
            $data = include $path;
            return is_array($data) ? $data : [];
        }
        return [];
    }
}

if (!function_exists('tenant_directory_write')) {
    function tenant_directory_write(array $dir): void
    {
        $path = tenant_directory_path();
        $dirPath = dirname($path);
        if (!is_dir($dirPath)) {
            @mkdir($dirPath, 0775, true);
        }
        $export = var_export($dir, true);
        $content = "<?php\nreturn " . $export . ";\n";
        @file_put_contents($path, $content, LOCK_EX);
    }
}

if (!function_exists('tenant_directory_set')) {
    function tenant_directory_set(string $email, string $slug): void
    {
        $email = strtolower(trim($email));
        $slug = strtolower(trim($slug));
        if (!$email || !$slug) { return; }
        $dir = tenant_directory_read();
        $dir[$email] = $slug;
        tenant_directory_write($dir);
    }
}

