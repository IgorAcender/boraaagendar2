<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

/**
 * Timezones library.
 *
 * Handles timezone related functionality.
 *
 * NOTE: Customized for Brazil-only deployments (plus UTC fallback).
 *
 * @package Libraries
 */
class Timezones
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * @var string
     */
    protected string $default = 'UTC';

    /**
     * @var array
     */
    protected array $timezones = [
        'UTC' => [
            'UTC' => 'UTC',
        ],
        'America' => [
            // Brazil (Amazonas/Acre/West)
            'America/Porto_Acre' => 'Porto_Acre (-4:00)',
            'America/Porto_Velho' => 'Porto_Velho (-4:00)',
            'America/Manaus' => 'Manaus (-4:00)',
            'America/Rio_Branco' => 'Rio_Branco (-4:00)',
            // Brazil (East)
            'America/Araguaina' => 'Araguaina (-3:00)',
            'America/Bahia' => 'Bahia (-3:00)',
            'America/Belem' => 'Belem (-3:00)',
            'America/Fortaleza' => 'Fortaleza (-3:00)',
            'America/Maceio' => 'Maceio (-3:00)',
            'America/Recife' => 'Recife (-3:00)',
            'America/Santarem' => 'Santarem (-3:00)',
            'America/Sao_Paulo' => 'Sao_Paulo (-3:00)',
            // Brazil (Fernando de Noronha)
            'America/Noronha' => 'Noronha (-2:00)',
        ],
        'Brazil' => [
            'Brazil/Acre' => 'Acre (-4:00)',
            'Brazil/West' => 'West (-4:00)',
            'Brazil/East' => 'East (-3:00)',
            'Brazil/DeNoronha' => 'DeNoronha (-2:00)',
        ],
    ];

    /**
     * Timezones constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('users_model');
    }

    /**
     * Get all timezones to a grouped array (by continent).
     *
     * @return array
     */
    public function to_grouped_array(): array
    {
        return $this->timezones;
    }

    /**
     * Get the default timezone value of the current system.
     *
     * @return string
     */
    public function get_default_timezone(): string
    {
        return date_default_timezone_get();
    }

    /**
     * Convert a date time value to a new timezone.
     *
     * @param string $value Provide a date time value as a string (format Y-m-d H:i:s).
     * @param string $from_timezone From timezone value.
     * @param string $to_timezone To timezone value.
     *
     * @return string
     *
     * @throws Exception
     */
    public function convert(string $value, string $from_timezone, string $to_timezone): string
    {
        if (!$to_timezone || $from_timezone === $to_timezone) {
            return $value;
        }

        $from = new DateTimeZone($from_timezone);

        $to = new DateTimeZone($to_timezone);

        $result = new DateTime($value, $from);

        $result->setTimezone($to);

        return $result->format('Y-m-d H:i:s');
    }

    /**
     * Get the timezone name for the provided value.
     *
     * @param string $value
     *
     * @return string|null
     */
    public function get_timezone_name(string $value): ?string
    {
        $timezones = $this->to_array();

        return $timezones[$value] ?? null;
    }

    /**
     * Get all timezones to a flat array.
     *
     * @return array
     */
    public function to_array(): array
    {
        return array_merge(...array_values($this->timezones));
    }
}

