<?php extend('layouts/account_layout'); ?>

<?php section('content'); ?>

<div id="global-login" class="container" style="max-width:480px">
    <h3 class="mb-4">Entrar</h3>

    <?php if (vars('error')): ?>
        <div class="alert alert-danger"><?= e(vars('error')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('entrar') ?>">
        <input type="hidden" name="<?= config('csrf_token_name') ?>" value="<?= html_escape(vars('csrf_token')) ?>">
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Senha</label>
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control" required>
                <button class="btn btn-outline-secondary" type="button" id="toggle-pass">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Entrar</button>
        </div>
    </form>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('toggle-pass');
  const input = document.getElementById('password');
  if(btn && input){
    btn.addEventListener('click', function(){
      input.type = input.type === 'password' ? 'text' : 'password';
      this.firstElementChild.classList.toggle('fa-eye');
      this.firstElementChild.classList.toggle('fa-eye-slash');
    });
  }
});
</script>
<?php end_section('scripts'); ?>

