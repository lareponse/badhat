<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= implode(' | ', slot('title')); ?></title>
</head>
<body>
    <?= implode(slot('content')); ?>
</body>
</html>