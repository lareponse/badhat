<?php


?>
<!DOCTYPE html>
<html lang="en">
<?php
$addbad_scaffold_mode = $addbad_scaffold_mode ?: 'in';
?>

<head>
    <meta charset="UTF-8">
    <title>Scaffold</title>
</head>

<body>
    <h1>Missing <?= $addbad_scaffold_mode === 'in' ? 'route' : 'render' ?>  end point <?= $quest['path'] ?></h1>
    <span>Choose file to create in: <strong><?= io()[$addbad_scaffold_mode === 'in' ? 0 : 1] ?>/</strong></span>

    <dl>
        <?php
        foreach (io_map($plan, $gps[0])[1] ?? [] as $depth => $response) {
            $handler = $response['handler'];
            $handlerArgs = empty($response['args']) ? 'no arguments' : "Expected arguments: '" . implode(',', $response['args']) . "'";
            $templateCode = "<?php\n// $handlerArgs\nreturn function (\$quest, \$request) {\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
        ?>
            <dt><strong>
                    <?= htmlspecialchars(
                        trim(
                            str_replace(io()[0], '', $handler),
                            '/'
                        )
                    ) ?>
                </strong></dt>
            <dd>
                <pre><?= $addbad_scaffold_mode === 'in' ? htmlspecialchars($templateCode) : '' ?></pre>
            </dd>
        <?php
        }
        ?>
    </dl>
</body>

</html>