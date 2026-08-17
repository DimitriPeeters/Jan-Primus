<?php

declare(strict_types=1);

use AEFS\Core\Url;

$error = $_GET['error'] ?? null;

$message = match ($error) {
    'missing'  => 'Gelieve e-mailadres en wachtwoord in te vullen.',
    'invalid'  => 'Ongeldig e-mailadres of wachtwoord.',
    'inactive' => 'Je account is nog niet actief.',
    'logout'   => 'Je bent succesvol afgemeld.',
    default    => null,
};

$success = $error === 'logout';
?>
<!DOCTYPE html>
<html lang="nl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AEFS - Aanmelden</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;
        }

        body{
            background:#eef2f7;
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
        }

        .card{
            width:420px;
            background:#fff;
            border-radius:12px;
            box-shadow:0 15px 40px rgba(0,0,0,.10);
            padding:40px;
        }

        h1{
            text-align:center;
            color:#1f2937;
            margin-bottom:35px;
        }

        label{
            display:block;
            margin-bottom:8px;
            font-weight:600;
            color:#374151;
        }

        input{
            width:100%;
            padding:12px;
            border:1px solid #d1d5db;
            border-radius:6px;
            margin-bottom:20px;
            font-size:15px;
        }

        input:focus{
            outline:none;
            border-color:#2563eb;
        }

        button{
            width:100%;
            border:none;
            padding:14px;
            border-radius:6px;
            background:#2563eb;
            color:#fff;
            font-size:16px;
            cursor:pointer;
            transition:.2s;
        }

        button:hover{
            background:#1d4ed8;
        }

        .message{
            margin-bottom:20px;
            padding:12px;
            border-radius:6px;
        }

        .error{
            background:#fee2e2;
            color:#991b1b;
        }

        .success{
            background:#dcfce7;
            color:#166534;
        }

        .footer{
            margin-top:25px;
            text-align:center;
        }

        .footer a{
            color:#2563eb;
            text-decoration:none;
        }

        .footer a:hover{
            text-decoration:underline;
        }

    </style>

</head>

<body>

<div class="card">

    <h1>AEFS</h1>

    <?php if ($message): ?>

        <div class="message <?= $success ? 'success' : 'error' ?>">

            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>

        </div>

    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(Url::to('/login'), ENT_QUOTES, 'UTF-8') ?>">

        <label for="email">
            E-mailadres
        </label>

        <input
            id="email"
            name="email"
            type="email"
            autocomplete="username"
            required
            autofocus
        >

        <label for="password">
            Wachtwoord
        </label>

        <input
            id="password"
            name="password"
            type="password"
            autocomplete="current-password"
            required
        >

        <button type="submit">
            Aanmelden
        </button>

    </form>

    <div class="footer">

        <a href="<?= htmlspecialchars(Url::to('/forgot-password'), ENT_QUOTES, 'UTF-8') ?>">
            Wachtwoord vergeten?
        </a>

    </div>

</div>

</body>

</html>