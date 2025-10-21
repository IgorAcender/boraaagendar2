<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.x
 * ---------------------------------------------------------------------------- */

class Migration_Change_photo_column_to_mediumtext extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if ($this->db->field_exists('photo', 'users')) {
            $fields = [
                'photo' => [
                    'name' => 'photo',
                    'type' => 'MEDIUMTEXT',
                    'null' => true,
                ],
            ];

            $this->dbforge->modify_column('users', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('photo', 'users')) {
            $fields = [
                'photo' => [
                    'name' => 'photo',
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ];

            $this->dbforge->modify_column('users', $fields);
        }
    }
}

