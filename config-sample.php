<?php
/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Easy!Appointments Configuration File
 *
 * Set your installation BASE_URL * without the trailing slash * and the database
 * credentials in order to connect to the database. You can enable the DEBUG_MODE
 * while developing the application.
 *
 * Set the default language by changing the LANGUAGE constant. For a full list of
 * available languages look at the /application/config/config.php file.
 *
 * IMPORTANT:
 * If you are updating from version 1.0 you will have to create a new "config.php"
 * file because the old "configuration.php" is not used anymore.
 */
class Config
{
    // ------------------------------------------------------------------------
    // GENERAL SETTINGS
    // ------------------------------------------------------------------------

    const BASE_URL = 'http://localhost';
    const LANGUAGE = 'english';
    const DEBUG_MODE = false;

    // ------------------------------------------------------------------------
    // DATABASE SETTINGS
    // ------------------------------------------------------------------------

    const DB_HOST = 'mysql';
    const DB_NAME = 'easyappointments';
    const DB_USERNAME = 'user';
    const DB_PASSWORD = 'password';

    // ------------------------------------------------------------------------
    // MULTITENANT PROVISIONING SETTINGS
    // ------------------------------------------------------------------------

    // Database server where tenant databases will be created.
    // In most setups this is the same host as DB_HOST.
    const PROVISION_DB_HOST = 'mysql';
    // Admin user with privileges to CREATE DATABASE/USER and GRANT on the server above.
    const PROVISION_DB_USERNAME = 'root';
    const PROVISION_DB_PASSWORD = 'rootpassword';
    // Optional prefixes for auto-generated tenant DB names and users.
    const DB_NAME_PREFIX = 'ea_';
    const DB_USER_PREFIX = 'ea_';

    // Optionally restrict allowed signup hostnames to a base domain
    // e.g. 'seuapp.com' means only subdomains like x.seuapp.com are allowed.
    // Leave empty to allow any host (not recommended for public SaaS).
    const ALLOWED_SIGNUP_BASE_DOMAIN = '';

    // ------------------------------------------------------------------------
    // GOOGLE reCAPTCHA (Signup)
    // ------------------------------------------------------------------------
    // If set, the /signup form will require reCAPTCHA validation.
    const RECAPTCHA_SITE_KEY = '';
    const RECAPTCHA_SECRET   = '';

    // ------------------------------------------------------------------------
    // GOOGLE CALENDAR SYNC
    // ------------------------------------------------------------------------

    const GOOGLE_SYNC_FEATURE = false; // Enter TRUE or FALSE
    const GOOGLE_CLIENT_ID = '';
    const GOOGLE_CLIENT_SECRET = '';
}
