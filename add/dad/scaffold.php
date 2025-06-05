<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <title>Scaffold</title>
</head>

<body>
    <h1>Missing <?= $in_or_out === 'in' ? 'route' : 'render' ?>  end point <?= $quest['path'] ?></h1>
    <span>Choose file to create in: <strong><?= realpath(__DIR__ . '/../io/route') ?>/</strong></span>

    <dl>
        <?php
        foreach ($quest['map'] as $depth => $handler_and_args) {
            list($handler, $args) = $handler_and_args;
            $handlerArgs = empty($args) ? 'no arguments' : "Expected arguments: '" . implode(',', $args) . "'";
            $templateCode = "<?php\n// $handlerArgs\nreturn function (\$quest) {\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
        ?>
            <dt><strong><?= htmlspecialchars($handler); ?></strong></dt>
            <dd>
                <pre><?= htmlspecialchars($templateCode) ?: '' ?></pre>
            </dd>
        <?php
        }
        ?>
    </dl>
</body>

</html>