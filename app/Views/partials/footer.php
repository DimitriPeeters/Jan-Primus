<?php



?>

<footer class="footer">

    <div class="footer-left">

        AEFS v2 &copy; <?= date('Y') ?>

    </div>

    <div class="footer-right">

        Gebouwd met PHP <?= PHP_VERSION ?>

    </div>

</footer>

<style>

.footer{

    height:60px;

    background:#ffffff;

    border-top:1px solid #e5e7eb;

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding:0 30px;

    color:#6b7280;

    font-size:14px;

}

@media(max-width:768px){

    .footer{

        flex-direction:column;

        justify-content:center;

        gap:6px;

        height:auto;

        padding:15px;

    }

}

</style>