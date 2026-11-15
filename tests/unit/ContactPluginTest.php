<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\sysplugins\contact\ContactPlugin;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use herbie\UrlManager;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class ContactPluginTest extends Unit
{
    public function testFormPostUsesSeeOtherRedirect(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $response = $this->invokePrivate($plugin, 'createRedirectResponse', ['contact', 'successful']);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('https://example.com/contact?status=successful', $response->getHeaderLine('Location'));
    }

    public function testInvalidMailAddressesAndHeaderInjectionAreRejected(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertTrue($this->invokePrivate($plugin, 'isValidMailAddress', ['visitor@example.com']));
        $this->assertFalse($this->invokePrivate($plugin, 'isValidMailAddress', ["visitor@example.com\r\nBcc: victim@example.com"]));
        $this->assertFalse($this->invokePrivate($plugin, 'isValidMailAddress', ['not-an-email']));
    }

    private function createUninitializedPlugin(): ContactPlugin
    {
        $plugin = (new ReflectionClass(ContactPlugin::class))->newInstanceWithoutConstructor();

        $this->setPrivateProperty($plugin, 'responseFactory', new ResponseFactory());
        $this->setPrivateProperty(
            $plugin,
            'urlManager',
            new UrlManager(
                (new ServerRequest())->withUri(new Uri('https://example.com/')),
                ['niceUrls' => true],
            ),
        );

        return $plugin;
    }

    private function invokePrivate(ContactPlugin $plugin, string $methodName, array $arguments): mixed
    {
        $method = new ReflectionMethod(ContactPlugin::class, $methodName);
        return $method->invokeArgs($plugin, $arguments);
    }

    private function setPrivateProperty(ContactPlugin $plugin, string $propertyName, mixed $value): void
    {
        $property = new ReflectionProperty(ContactPlugin::class, $propertyName);
        $property->setValue($plugin, $value);
    }
}
