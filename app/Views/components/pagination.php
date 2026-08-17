<?php



/*

$current

$pages

$url

*/

$current ??= 1;

$pages ??= 1;

$url ??= '';

if($pages<=1){

    return;

}

?>

<nav class="pagination">

<?php for($i=1;$i<=$pages;$i++): ?>

<a

href="<?= $url ?>?page=<?= $i ?>"

class="<?= $i==$current?'active':'' ?>"

>

<?= $i ?>

</a>

<?php endfor; ?>

</nav>