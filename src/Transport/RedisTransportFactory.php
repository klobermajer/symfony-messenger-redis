<?php

namespace Krak\SymfonyMessengerRedis\Transport;

use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Transport\{
    TransportInterface,
    TransportFactoryInterface,
    Serialization\SerializerInterface
};

final class RedisTransportFactory implements TransportFactoryInterface
{
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface {
        return RedisTransport::fromDsn($serializer, $dsn, $options);
    }

    /**
     * Symfony's redis factory also matches on the redis:// prefix, so to support using
     * both redis adapters at the same time, the `use_lists` option allows you to opt out
     * of this implementation.
     */
    public function supports(string $dsn, array $options): bool {
        $parsedUrl = \parse_url($dsn);
        if (!$parsedUrl) {
            throw new InvalidArgumentException(sprintf('The given Redis DSN "%s" is invalid.', $dsn));
        }

        \parse_str($parsedUrl['query'] ?? '', $query);

        $useKrak = isset($query['use_krak_redis']) ? filter_var($query['use_krak_redis'], FILTER_VALIDATE_BOOLEAN) : false;

        return (strpos($dsn, 'redis://') === 0 || strpos($dsn, 'rediss://') === 0) && $useKrak;
    }
}
