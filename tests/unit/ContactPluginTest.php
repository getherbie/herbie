<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Application;
use herbie\Config;
use herbie\Page;
use herbie\Translator;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\plugins\contact\ContactPlugin;
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

    public function testCsrfTokenIsGeneratedAndValidated(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $token = $plugin->csrfToken();
        $this->assertIsString($token);
        $this->assertStringContainsString(':', $token);

        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($plugin, ['csrf_token' => $token]));
        $this->assertFalse($method->invoke($plugin, ['csrf_token' => 'invalid']));
        $this->assertFalse($method->invoke($plugin, ['csrf_token' => '']));
    }

    public function testFilterFormDataSanitizesInput(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $method = new ReflectionMethod(ContactPlugin::class, 'filterFormData');
        $method->setAccessible(true);

        $filtered = $method->invoke($plugin, [
            'name' => '  <b>John</b>  ',
            'email' => '  Foo@Bar.com  ',
            'message' => 'Hello <script>x</script> world',
            'antispam' => '<i>bot</i>',
            'phone' => '<i>callme</i>',
        ]);

        $this->assertSame('  John  ', $filtered['name']);
        $this->assertSame('Foo@Bar.com', $filtered['email']);
        $this->assertSame('Hello x world', $filtered['message']);
        $this->assertSame('bot', $filtered['antispam']);
        $this->assertSame('callme', $filtered['phone']);
    }

    public function testValidateFormDataReturnsErrors(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $method = new ReflectionMethod(ContactPlugin::class, 'validateFormData');
        $method->setAccessible(true);

        $errors = $method->invoke($plugin, [
            'name' => '',
            'email' => 'not-an-email',
            'message' => '   ',
        ]);

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('message', $errors);

        $valid = $method->invoke($plugin, [
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'Hello',
        ]);
        $this->assertSame([], $valid);
    }

    public function testCachedContactPageThrows(): void
    {
        $plugin = (new ReflectionClass(ContactPlugin::class))->newInstanceWithoutConstructor();

        $this->setPrivateProperty(
            $plugin,
            'urlManager',
            new UrlManager(
                (new ServerRequest())->withUri(new Uri('https://example.com/contact')),
                ['niceUrls' => true],
            ),
        );

        $pageList = new PageList();
        $page = new Page(['route' => 'contact', 'cached' => true]);
        $pageList->addItem($page);

        $pageRepository = new class ($pageList) implements PageRepositoryInterface {
            public function __construct(private PageList $pageList) {}
            public function find(string $id): ?Page
            {
                return null;
            }
            public function findAll(): PageList
            {
                return $this->pageList;
            }
        };

        $this->setPrivateProperty($plugin, 'pageRepository', $pageRepository);
        $this->setPrivateProperty($plugin, 'config', new Config(['plugins' => ['contact' => ['secret' => 'test-secret']]]));

        $this->expectException(\RuntimeException::class);
        $plugin->contact();
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
        $this->setPrivateProperty($plugin, 'config', new Config(['plugins' => ['contact' => ['secret' => 'test-secret']]]));
        $this->setPrivateProperty($plugin, 'pageRepository', new class implements PageRepositoryInterface {
            public function find(string $id): ?Page
            {
                return null;
            }
            public function findAll(): PageList
            {
                return new PageList();
            }
        });

        $translator = new Translator('en');
        $translator->addPath('contact', Application::getHerbiePath('/plugins/contact/messages'));
        $translator->init();
        $this->setPrivateProperty($plugin, 'translator', $translator);

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
