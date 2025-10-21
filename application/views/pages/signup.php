<?php extend('layouts/account_layout'); ?>

<?php section('content'); ?>

<div id="signup" class="container">
    <h3 class="mb-4">Criar sua conta</h3>

    <form method="post" action="<?= site_url('signup/store') ?>">
        <input type="hidden" name="<?= config('csrf_token_name') ?>" value="<?= html_escape(vars('csrf_token')) ?>">
        <div class="mb-3">
            <label class="form-label">SubdomÃ­nio (host)</label>
            <input type="text" name="host" class="form-control" placeholder="cliente.seuapp.com" required>
            <small class="text-muted">Certifique-se de apontar o DNS para este app.</small>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Empresa</label>
                    <input type="text" name="company_name" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">E-mail do administrador</label>
                    <input type="email" name="admin_email" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">UsuÃ¡rio do administrador</label>
                    <input type="text" name="admin_username" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Senha do administrador</label>
                    <input type="password" name="admin_password" class="form-control" minlength="8" required>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            O banco de dados e o usuÃ¡rio do cliente serÃ£o criados automaticamente com base no subdomÃ­nio informado.
        </div>

        <?php $recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY') ?: (defined('Config::RECAPTCHA_SITE_KEY') ? Config::RECAPTCHA_SITE_KEY : ''); ?>
        <?php if (!empty($recaptchaSiteKey)): ?>
            <div class="mb-3">
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <div class="g-recaptcha" data-sitekey="<?= html_escape($recaptchaSiteKey) ?>"></div>
            </div>
        <?php endif; ?>

        <div class="d-grid mt-3">
            <button type="submit" class="btn btn-primary">Criar conta</button>
        </div>
    </form>
</div>

<?php end_section('content'); ?>
