<?php extend('layouts/account_layout'); ?>

<?php section('content'); ?>

<?php
    $baseDomain = getenv('ALLOWED_SIGNUP_BASE_DOMAIN') ?: (defined('Config::ALLOWED_SIGNUP_BASE_DOMAIN') ? Config::ALLOWED_SIGNUP_BASE_DOMAIN : '');
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
?>

<div id="signup" class="container">
    <h3 class="mb-4">Criar sua conta</h3>

    <form method="post" action="<?= site_url('signup/store') ?>">
        <input type="hidden" name="<?= config('csrf_token_name') ?>" value="<?= html_escape(vars('csrf_token')) ?>">

        <?php if (!empty($baseDomain)) : ?>
            <div class="mb-2">
                <label class="form-label">Nome do subdomínio</label>
                <div class="input-group">
                    <input type="text" name="subdomain" class="form-control" placeholder="minhaempresa" required>
                    <span class="input-group-text">.<?= html_escape($baseDomain) ?></span>
                </div>
                <small class="text-muted">Seu endereço será: https://<strong>minhaempresa</strong>.<?= html_escape($baseDomain) ?></small>
            </div>
            <details class="mb-3">
                <summary>Usar um host completo (avançado)</summary>
                <input type="text" name="host" class="form-control mt-2" placeholder="cliente.<?= html_escape($baseDomain) ?>">
                <small class="text-muted">Preencha apenas se quiser informar o host inteiro manualmente.</small>
            </details>
        <?php else: ?>
            <div class="mb-3">
                <label class="form-label">Subdomínio (host)</label>
                <input type="text" name="host" class="form-control" placeholder="<?= html_escape($currentHost ?: 'cliente.seuapp.com') ?>" required>
                <small class="text-muted">Informe o host completo deste app (exatamente como aparece na barra de endereço).</small>
            </div>
        <?php endif; ?>

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
                    <label class="form-label">Usuário do administrador</label>
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
            O banco de dados e o usuário do cliente serão criados automaticamente.
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

