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

if (!function_exists('setting')) {
    /**
     * Get / set the specified setting value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * Example "Get":
     *
     * $company_name = session('company_name', FALSE);
     *
     * Example "Set":
     *
     * setting(['company_name' => 'ACME Inc']);
     *
     * @param array|string|null $key Setting key.
     * @param mixed|null $default Default value in case the requested setting has no value.
     *
     * @return mixed|NULL Returns the requested value or NULL if you assign a new setting value.
     *
     * @throws InvalidArgumentException
     */
    function setting(array|string|null $key = null, mixed $default = null): mixed
    {
        /** @var EA_Controller $CI */
        $CI = &get_instance();

        // If database is not available (e.g. during /signup before any tenant exists),
        // gracefully return defaults instead of triggering DB calls.
        $db_available = isset($CI->db) && is_object($CI->db) && method_exists($CI->db, 'table_exists');

        if (!$db_available || !$CI->db->table_exists('settings')) {
            // When $key is an array (set operation), skip silently.
            return is_array($key) ? null : $default;
        }

        $CI->load->model('settings_model');

        if (empty($key)) {
            throw new InvalidArgumentException('The $key argument cannot be empty.');
        }

        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $setting = $CI->settings_model
                    ->query()
                    ->where('name', $name)
                    ->get()
                    ->row_array();

                if (empty($setting)) {
                    $setting = [
                        'name' => $name,
                    ];
                }

                $setting['value'] = $value;

                $CI->settings_model->save($setting);
            }

            return null;
        }

        $setting = $CI->settings_model
            ->query()
            ->where('name', $key)
            ->get()
            ->row_array();

        return $setting['value'] ?? $default;
    }
}
