<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <title>Scaffold</title>
    <style>
        dt:nth-child(2n) {
            margin-bottom: 1rem;
            color:darkgray;
        }
    </style>
</head>

<body>
    <h1>Missing <?= (io_state($quest) & IO_IN) ? 'render' : 'route' ?> end point <?= $quest[IO_PATH] ?></h1>
    <span>Choose file to create in: <strong><?= realpath(__DIR__ . '/../io/route') ?>/</strong></span>

    <dl>
        <?php foreach ($quest[IO_MAP] as $depth => $checkpoint): ?>
            <dt><strong><?= htmlspecialchars($checkpoint[0]); ?></strong></dt>
        <?php endforeach; ?>
        <dd>
            <?php
            list($handler, $args) = $handler_and_args;
            $handlerArgs = empty($args) ? 'no arguments' : "Expected arguments: '" . implode(',', $args) . "'";
            $templateCode = "<?php\n// $handlerArgs\nreturn function (\$quest) {\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
            ?>
            <pre><?= htmlspecialchars($templateCode) ?: '' ?></pre>
        </dd>
    </dl>

    <?php vd($quest, 'quest');?>
</body>

</html>