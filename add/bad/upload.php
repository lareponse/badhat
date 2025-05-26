<?php
/**
 * Upload file with security checks.
 *
 * @param array  $f         $_FILES entry
 * @param string $dst       target dir
 * @param int    $maxSize   max bytes allowed
 * @param array  $types     [ 'ext'=>[ 'mime1', ... ], … ]
 * @param string $prefix    optional filename prefix
 * @return array            ['ok'=>bool,'error'=>string,'file'=>string,'size'=>int,'type'=>string]
 */
function upload(array $f, string $dst, int $maxSize, array $types, string $prefix = ''): array
{
    if (
        $f['error'] !== UPLOAD_ERR_OK ||
        !is_uploaded_file($f['tmp_name']) ||
        $f['size'] <= 0 ||
        $f['size'] > $maxSize
    ) {
        return ['ok' => false, 'error' => 'upload failed'];
    }

    $ext   = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $mimes = $types[$ext] ?? null;
    if (!$mimes) {
        return ['ok' => false, 'error' => 'type not allowed'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, $mimes, true)) {
        return ['ok' => false, 'error' => 'mimetype mismatch'];
    }

    $base = preg_replace('/[^a-z0-9_-]/i', '', pathinfo($f['name'], PATHINFO_FILENAME));
    $base = substr($base, 0, 40);
    $name = $prefix
        . $base
        . '_'
        . date('YmdHis')
        . bin2hex(random_bytes(4))
        . ".$ext";

    $root = rtrim(request()['root'], '/');
    $dir  = "$root/public/" . trim($dst, '/');
    is_dir($dir) ?: mkdir($dir, 0755, true);

    $path = "$dir/$name";
    if (!move_uploaded_file($f['tmp_name'], $path)) {
        return ['ok' => false, 'error' => 'move failed'];
    }
    chmod($path, 0644);

    return [
        'ok'   => true,
        'file' => trim($dst, '/') . "/$name",
        'size' => $f['size'],
        'type' => $mime,
    ];
}
