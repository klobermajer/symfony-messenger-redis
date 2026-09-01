<?php

namespace Krak\SymfonyMessengerRedis\Tests\Feature;

use Krak\SymfonyMessengerRedis\MessengerRedisBundle;
use Krak\SymfonyMessengerRedis\Tests\Feature\Fixtures\KrakRedisMessage;
use Krak\SymfonyMessengerRedis\Tests\Feature\Fixtures\SfRedisMessage;
use Krak\SymfonyMessengerRedis\Transport\RedisTransport;
use Krak\SymfonyMessengerRedis\Transport\RedisTransportFactory;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class BundleTest extends KernelTestCase
{
    use RedisSteps;

    protected static function getKernelClass(): string {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(MessengerRedisBundle::class);
        $kernel->addTestConfig(__DIR__ . '/Fixtures/framework-config.yaml');
        $kernel->addTestCompilerPass(new PublicServicePass('/(Krak.*|messenger.default_serializer|.*MessageBus.*)/'));
        $kernel->handleOptions($options);

        return $kernel;
    }

    protected function setUp(): void {
        parent::setUp();
        $this->given_a_redis_client_is_configured_with_a_fresh_redis_db();
    }

    /** @test */
    public function registers_the_redis_transport_factory_as_a_service() {
        self::bootKernel();

        $this->assertInstanceOf(RedisTransportFactory::class, self::getContainer()->get(RedisTransportFactory::class));
    }

    /** @test */
    public function registers_the_redis_message_bus_integration() {
        // Arrange: boot kernel with redis-config.yaml
        $this->given_the_kernel_is_booted_with_redis_config();

        // Act: dispatch the krak redis message on the bus
        /** @var MessageBusInterface $bus */
        $bus = $this->messageBus();
        $bus->dispatch(new KrakRedisMessage());

        // Assert: verify the message was pushed to krak redis transport
        $transport = $this->createKrakRedisTransport();
        $res = $transport->get();
        $this->assertCount(1, $res);
        $this->assertInstanceOf(KrakRedisMessage::class, $res[0]->getMessage());
    }

    /** @test */
    public function allows_sf_redis_transport() {
        $this->given_the_kernel_is_booted_with_redis_config();

        // Act: dispatch the sf message on the bus
        /** @var MessageBusInterface $bus */
        $bus = $this->messageBus();
        $bus->dispatch(new SfRedisMessage());

        // Assert: verify the message was not pushed to krak redis transport
        $transport = $this->createKrakRedisTransport();
        $this->assertEquals(0, $transport->getMessageCount());
    }

    private function given_the_kernel_is_booted_with_redis_config() {
        self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/Fixtures/redis-config.yaml');
        }]);
    }

    private function createKrakRedisTransport(): RedisTransport {
        $container = self::getContainer();
        /** @var RedisTransportFactory $transportFactory */
        $transportFactory = $container->get(RedisTransportFactory::class);

        return $transportFactory->createTransport(getenv('KRAK_REDIS_DSN'), [
            'blocking_timeout' => 1,
        ], $container->get('messenger.default_serializer'));
    }

    private function messageBus(): MessageBusInterface {
        return self::getContainer()->get(MessageBusInterface::class);
    }
}
