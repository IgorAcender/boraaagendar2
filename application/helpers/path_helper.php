<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * Example:
     *
     * $logs_path = storage_path('logs'); // Returns "/path/to/installation/dir/storage/logs"
     *
     * @param string $path
     *
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return FCPATH . 'storage/' . trim($path);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the current installation.
     *
     * $controllers_path = base_path('application/controllers'); // Returns "/path/to/installation/dir/application/controllers"
     *
     * @param string $path
     *
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return FCPATH . trim($path);
    }
}

if (!function_exists('uploads_path')) {
    /**
     * Get the path to the uploads folder (tenant-scoped).
     *
     * @param string $path Relative path under uploads directory.
     * @param bool $ensure_dir When true, ensures the directory exists.
     *
     * @return string
     */
    function uploads_path(string $path = '', bool $ensure_dir = true): string
    {
        $host = function_exists('tenant_current_host') ? (tenant_current_host() ?: 'default') : 'default';
        $safeHost = preg_replace('/[^a-z0-9._-]+/i', '_', $host);
        $base = FCPATH . 'storage/uploads/' . $safeHost . '/';

        if ($ensure_dir && !is_dir($base)) {
            @mkdir($base, 0775, true);
        }

        return $base . ltrim($path, '/');
    }
}
