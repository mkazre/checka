<?php
/**
 * @var \Throwable $exception
 */
?>
[<?= get_class($exception) ?>]
<?= $exception->getMessage() ?>

at <?= clean_path($exception->getFile()) ?>:<?= $exception->getLine() ?>


<?= $exception->getTraceAsString() ?>
