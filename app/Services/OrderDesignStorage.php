<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

final class OrderDesignStorage
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_PIXELS = 24000000;
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(private string $directory) {}

    public function store(?array $file): ?array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No fue posible cargar la imagen de referencia.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('La imagen de referencia debe pesar como maximo 5 MB.');
        }
        $temporary = (string) ($file['tmp_name'] ?? '');
        if ($temporary === '' || !is_uploaded_file($temporary)) {
            throw new InvalidArgumentException('El archivo de referencia no es una carga valida.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary);
        if (!is_string($mime) || !isset(self::EXTENSIONS[$mime])) {
            throw new InvalidArgumentException('La referencia debe ser una imagen JPG, PNG o WebP.');
        }
        $dimensions = @getimagesize($temporary);
        if (!is_array($dimensions) || ($dimensions[0] * $dimensions[1]) > self::MAX_PIXELS) {
            throw new InvalidArgumentException('Las dimensiones de la imagen de referencia no son validas.');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento de disenos.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::EXTENSIONS[$mime];
        if (!move_uploaded_file($temporary, $this->directory . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException('No fue posible guardar la imagen de referencia.');
        }
        $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? 'referencia')));
        return [
            'internal_name' => $filename,
            'original_name' => mb_substr($original, 0, 255),
            'mime' => $mime,
            'size' => $size,
        ];
    }

    public function remove(?string $filename): void
    {
        if (!$filename || !preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $filename)) return;
        $path = $this->directory . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) @unlink($path);
    }

    public function path(string $filename): string
    {
        if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $filename)) {
            throw new InvalidArgumentException('La referencia solicitada no es valida.');
        }
        return $this->directory . DIRECTORY_SEPARATOR . $filename;
    }
}
