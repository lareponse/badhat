<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scaffold</title>
</head>

<body>
    <h1>Missing route: <?= request()['path'] ?></h1>
    <strong>Choose route file to create:</strong>
    <dl>
        <?php
        foreach (io_candidates(request()['route_root'], true) as $depth => $response) {
            $handler = $response['handler'];
            $handlerArgs = empty($response['args']) ? 'none' : implode(',', $response['args']);
            $templateCode = "<?php\nreturn function (...\$args) {\n\t// Expected arguments: function($handlerArgs)\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
        ?>
            <dt><strong><?= htmlspecialchars($handler) ?></strong></dt>
            <dd>
                <pre><?= htmlspecialchars($templateCode) ?></pre>
            </dd>
        <?php
        }
        ?>
    </dl>
</body>

</html>