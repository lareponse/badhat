Missing <?= ($quest[QST_CORE] & QST_PULL) ? 'render' : 'route' ?> end point <?= http_in() ?>


Choose file to create in: <?= realpath(__DIR__ . '/../../../app/io/route') ?>

<?php foreach ((io_route(http_in())) as $handler => $args): ?>
<?= PHP_EOL.htmlspecialchars($handler); ?>
    <?php
    $handlerArgs = empty($args) ? 'no arguments' : "Expected arguments: '" . implode(',', $args) . "'";
    $templateCode = "<?php\n// $handlerArgs\nreturn function (\$quest) {\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
    ?>
<?php endforeach; ?>
