<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

/**
 * Immutable value object holding the SSL-related mysqli connection options.
 *
 * @see MysqliConnectionParameters
 */
final readonly class MysqliSslOptions
{
    public function __construct(
        public bool $enabled = false,
        public string $key = '',
        public string $cert = '',
        public string $caCert = '',
        public string $caPath = '',
        public string $cipher = '',
    ) {}

    /**
     * @param array<string, mixed> $params Raw connection parameters, as accepted by
     * {@see \PhpDb\Mysql\Connection::setConnectionParameters()}.
     */
    public static function fromArray(array $params): self
    {
        return new self(
            enabled: (bool) ($params['use_ssl'] ?? false),
            key: (string) ($params['client_key'] ?? ''),
            cert: (string) ($params['client_cert'] ?? ''),
            caCert: (string) ($params['ca_cert'] ?? ''),
            caPath: (string) ($params['ca_path'] ?? ''),
            cipher: (string) ($params['cipher'] ?? ''),
        );
    }
}
