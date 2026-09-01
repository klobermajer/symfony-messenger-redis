<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Krak\SymfonyMessengerRedis\Transport\RedisTransportFactory;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(RedisTransportFactory::class)
        ->tag('messenger.transport_factory', ['priority' => 10]);
};
