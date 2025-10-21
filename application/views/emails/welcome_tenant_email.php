<?php
/**
 * Local variables.
 *
 * @var string $subject
 * @var string $host
 * @var string $admin_username
 * @var string $company_name
 */
?>
<html lang="pt-BR">
<head>
    <title><?= e($subject) ?> | Easy!Appointments</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <style>body{font:13px arial,helvetica,tahoma}</style>
    </head>
<body>

<div class="email-container" style="width:650px;border:1px solid #eee;margin:30px auto;">
    <div id="header" style="background-color:#429a82;height:45px;padding:10px 15px;">
        <strong id="logo" style="color:#fff;font-size:20px;margin-top:10px;display:inline-block">
            <?= e($company_name) ?>
        </strong>
    </div>

    <div id="content" style="padding:10px 15px;min-height:200px">
        <h2 style="font-weight:normal;margin-top:0;">Bem-vindo!</h2>
        <p>Seu ambiente foi criado com sucesso.</p>
        <ul>
            <li><strong>Endereço:</strong> <a href="https://<?= e($host) ?>" target="_blank">https://<?= e($host) ?></a></li>
            <li><strong>Usuário admin:</strong> <?= e($admin_username) ?></li>
        </ul>
        <p>Use a senha definida no cadastro para acessar sua conta.</p>
    </div>

    <div id="footer" style="padding:10px;text-align:center;margin-top:10px;border-top:1px solid #EEE;background:#FAFAFA;">
        Powered by
        <a href="https://easyappointments.org" style="text-decoration:none;">Easy!Appointments</a>
        |
        <a href="https://<?= e($host) ?>" style="text-decoration:none;"><?= e($company_name) ?></a>
    </div>
</div>

</body>
</html>

