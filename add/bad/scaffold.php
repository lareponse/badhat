<h1>Missing route: <?= $path ?></h1>
<strong>Choose route file to create:</strong>
<dl>
<?php
foreach ($request['candidates'] as $depth => $response) {
    $handler = $response['handler'];
    $handlerArgs = empty($response['args']) ? 'none' : implode(',', $response['args']);
    $templateCode = "<?php\nreturn function (...\$args) {\n\t// Expected arguments: function($handlerArgs)\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
    ?>
    <dt><strong><?= htmlspecialchars($handler) ?></strong></dt>
    <dd><pre><?= htmlspecialchars($templateCode) ?></pre></dd>
    <?php
}
?>
</dl>