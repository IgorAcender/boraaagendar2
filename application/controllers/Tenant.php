<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tenant controller.
 *
 * Switch current tenant by slug via path, e.g. /cliente/<slug> or /c/<slug>.
 */
class Tenant extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Switch to the provided tenant and redirect to the app root.
     *
     * @param string $slug
     */
    public function switch($slug = ''): void
    {
        $slug = preg_replace('/[^a-z0-9-_.]+/i', '', (string) $slug);

        if (!$slug) {
            redirect('/');
            return;
        }

        // Persist for subsequent requests
        setcookie('TENANT_SLUG', $slug, time() + 60 * 60 * 24 * 30, '/');

        redirect('/');
    }
}

