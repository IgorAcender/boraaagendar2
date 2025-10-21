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
                    <div class="input-group">
                        <input type="password" id="admin-password" name="admin_password" class="form-control" minlength="8" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggle-password" aria-label="Mostrar/ocultar senha">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Confirmar senha</label>
                    <div class="input-group">
                        <input type="password" id="admin-password-confirm" name="admin_password_confirm" class="form-control" minlength="8" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggle-password-confirm" aria-label="Mostrar/ocultar senha">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
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

<?php section('scripts'); ?>
<script>
    (function(){
        function toggle(id, btn){
            const input = document.getElementById(id);
            if(!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            if (btn && btn.firstElementChild) {
                btn.firstElementChild.classList.toggle('fa-eye');
                btn.firstElementChild.classList.toggle('fa-eye-slash');
            }
        }

        document.addEventListener('DOMContentLoaded', function(){
            const t1 = document.getElementById('toggle-password');
            const t2 = document.getElementById('toggle-password-confirm');
            if (t1) t1.addEventListener('click', function(){ toggle('admin-password', t1); });
            if (t2) t2.addEventListener('click', function(){ toggle('admin-password-confirm', t2); });
        });
    })();
</script>
<?php end_section('scripts'); ?>
