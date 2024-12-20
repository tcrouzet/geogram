<?php

include 'header.php';
$projectRoot = dirname(__DIR__,2);
$parsedown = new Parsedown();
$markdownContent = file_get_contents($projectRoot .'/assets/md/help_fr.md');

//placeholder
$markdownContent = preg_replace_callback('/{([A-Z_]+)}/', function($matches) {
    $constantName = $matches[1];
    if (defined($constantName)) {
        return constant($constantName);
    }
    return $matches[0]; // Retourne le placeholder s'il n'est pas trouvé
}, $markdownContent);

$htmlContent = $parsedown->text($markdownContent);
?>

<main>
<div id="splash">

<?= $htmlContent ?>

</div>
</main>
