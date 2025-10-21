<?php extend('layouts/account_layout'); ?>

<?php section('content'); ?>

<div id="signup-success" class="container">
    <h3 class="mb-3">Conta criada com sucesso</h3>
    <p>Seu ambiente foi provisionado.</p>
    <ul>
        <li><strong>Endereço:</strong> <a href="https://<?= html_escape(vars('host')) ?>" target="_blank">https://<?= html_escape(vars('host')) ?></a></li>
        <li><strong>Usuário admin:</strong> <?= html_escape(vars('admin_username')) ?></li>
        <li><strong>E-mail admin:</strong> <?= html_escape(vars('admin_email')) ?></li>
    </ul>
    <p>Você já pode acessar e fazer login no link acima.</p>
</div>

<?php end_section('content'); ?>

