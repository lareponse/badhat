<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scaffold</title>
</head>

<body>
    <h1>Missing end point <?= request()['path'] ?></h1>
    <span>Choose route file to create in: <strong><?= io('i') ?>/</strong></span>

        <dl>
            <?php
            foreach (io_candidates(io('i'), true) as $depth => $response) {
                $handler = $response['handler'];
                $handlerArgs = empty($response['args']) ? 'none' : implode(',', $response['args']);
                $templateCode = "<?php\n// Expected arguments: $handlerArgs\nreturn function (...\$args) {\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
            ?>
                <dt><strong>
                <?= htmlspecialchars(
                    trim(
                        str_replace(io('i'), '', $handler),
                        '/'
                    )
                ) ?>
                </strong></dt>
                <dd>
                    <pre><?= htmlspecialchars($templateCode) ?></pre>
                </dd>
            <?php
            }
            ?>
        </dl>
</body>

</html>