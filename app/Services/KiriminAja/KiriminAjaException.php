<?php

namespace App\Services\KiriminAja;

use RuntimeException;

/**
 * Error dari / menuju KiriminAja. Pesan sudah ramah-pengguna (Bahasa Indonesia).
 */
class KiriminAjaException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?array $body = null,
    ) {
        parent::__construct($message, $status);
    }

    public static function notConfigured(): self
    {
        return new self('API Key KiriminAja belum dikonfigurasi. Isi KIRIMINAJA_API_KEY di file .env server.', 503);
    }
}

