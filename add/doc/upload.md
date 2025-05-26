# File Upload Module

## Features
- **Zero globals**: all config via parameters.
- **Strict size check**: rejects 0-bytes & files > `$maxSize`.
- **MIME enforcement**: uses `finfo` against allowed types by extension.
- **Safe names**: sanitizes, timestamps, randomizes.
- **Auto mkdir**: creates target dir (`0755`).
- **Secure perms**: final file set to `0644`.

## Limitations
- No image-specific validations (dimensions, EXIF).
- No virus/malware scan.
- No resume/chunked upload support.
- Caller must define `$types` map every time.
- No cleanup helper (deletion removed).

## Sample Usage

```php
$types = [
    'pdf'  => ['application/pdf'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'jpg'  => ['image/jpeg'],
    // ...
];

$result = upload(
    $_FILES['file'],
    'uploads/docs',
    5 * 1024 * 1024,   // 5 MB
    $types,
    'usr_'             // optional prefix
);

if ($result['ok']) {
    echo "Saved: {$result['file']} ({$result['size']} bytes)";
} else {
    echo "Error: {$result['error']}";
}
