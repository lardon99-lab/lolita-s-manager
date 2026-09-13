<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

final class ProductImageStorage
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(private string $directory) {}

    public function store(?array $file): string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return 'default_product.png';
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !isset($file['tmp_name'], $file['size'])) {
            throw new InvalidArgumentException('La imagen no se recibio correctamente.');
        }
        if ((int) $file['size'] <= 0 || (int) $file['size'] > self::MAX_BYTES || !is_uploaded_file((string) $file['tmp_name'])) {
            throw new InvalidArgumentException('La imagen no es valida o supera 5 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if (!is_string($mime) || !isset(self::EXTENSIONS[$mime])) {
            throw new InvalidArgumentException('Solo se permiten imagenes JPG, PNG o WebP.');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new RuntimeException('No se pudo preparar el directorio de imagenes.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . self::EXTENSIONS[$mime];
        if (!move_uploaded_file((string) $file['tmp_name'], $this->directory . '/' . $filename)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }
        return $filename;
    }

    public function remove(string $filename): void
    {
        if ($filename === '' || $filename === 'default_product.png' || basename($filename) !== $filename) return;
        $path = $this->directory . '/' . $filename;
        if (is_file($path)) @unlink($path);
    }
}
