<?php

declare(strict_types=1);

use AEFS\Core\Auth;

$user = Auth::user();

?>
<header class="header">

    <div class="header-left">

        <h1><?= htmlspecialchars($title ?? 'AEFS', ENT_QUOTES, 'UTF-8') ?></h1>

    </div>

    <div class="header-right">

        <div class="user-info">

            <div class="avatar">

                <?= strtoupper(substr($user['voornaam'] ?? '?', 0, 1)) ?>

            </div>

            <div>

                <strong>

                    <?= htmlspecialchars(
                        ($user['voornaam'] ?? '') . ' ' . ($user['achternaam'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </strong>

                <br>

                <small>

                    <?= htmlspecialchars(
                        ucfirst($user['rol'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </small>

            </div>

        </div>

    </div>

</header>

<style>

.header{

    height:75px;

    background:#ffffff;

    border-bottom:1px solid #e5e7eb;

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding:0 30px;

}

.header h1{

    margin:0;

    font-size:28px;

    color:#1f2937;

}

.header-right{

    display:flex;

    align-items:center;

    gap:20px;

}

.user-info{

    display:flex;

    align-items:center;

    gap:12px;

}

.avatar{

    width:44px;

    height:44px;

    border-radius:50%;

    background:#2563eb;

    color:#fff;

    display:flex;

    justify-content:center;

    align-items:center;

    font-weight:bold;

    font-size:18px;

}

.user-info small{

    color:#6b7280;

}

</style>