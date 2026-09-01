<?php

namespace Krak\SymfonyMessengerRedis\Tests\Feature;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PublicServicePass implements CompilerPassInterface
{
    private $regex;

    public function __construct(string $regex = '') {
        $this->regex = $regex;
    }

    public function process(ContainerBuilder $container): void {
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($this->matches($id)) {
                $definition->setPublic(true);
            }
        }

        foreach ($container->getAliases() as $id => $alias) {
            if ($this->matches($id)) {
                $alias->setPublic(true);
            }
        }
    }

    private function matches(string $id): bool {
        return '' === $this->regex || 1 === preg_match($this->regex, $id);
    }
}
