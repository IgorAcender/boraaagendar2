<?php extend('layouts/account_layout'); ?>

<?php section('content'); ?>

<div id="signup-success" class="container">
    <h3 class="mb-3">Conta criada com sucesso</h3>
    <p>Seu ambiente foi provisionado.</p>
    <ul>
        <?php $host = html_escape(vars('host')); $slug = html_escape(vars('slug')); ?>
        <li><strong>Endereço:</strong> <a href="https://<?= $host ?>" target="_blank">https://<?= $host ?></a></li>
        <li><strong>Link por caminho:</strong> <a href="<?= site_url('cliente/' . $slug) ?>" target="_blank"><?= site_url('cliente/' . $slug) ?></a></li>
        <li><strong>Usuário admin:</strong> <?= html_escape(vars('admin_username')) ?></li>
        <li><strong>E-mail admin:</strong> <?= html_escape(vars('admin_email')) ?></li>
    </ul>
    <p>Você já pode acessar e fazer login em qualquer um dos links acima.</p>
</div>

<?php end_section('content'); ?>
