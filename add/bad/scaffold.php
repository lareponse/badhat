<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scaffold</title>
</head>

<body>
    <h1>Missing end point <?= request()['path'] ?></h1>
    <span>Choose route file to create in: <strong><?= request()['route_root'] ?>/</strong></span>

        <dl>
            <?php
            foreach (io_candidates(request()['route_root'], true) as $depth => $response) {
                $handler = $response['handler'];
                $handlerArgs = empty($response['args']) ? 'none' : implode(',', $response['args']);
                $templateCode = "<?php\n// Expected arguments: $handlerArgs\nreturn function (...\$args) {\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
            ?>
                <dt><strong><?= htmlspecialchars(str_replace(request()['route_root'], '', $handler)) ?></strong></dt>
                <dd>
                    <pre><?= htmlspecialchars($templateCode) ?></pre>
                </dd>
            <?php
            }
            ?>
        </dl>
</body>

</html>