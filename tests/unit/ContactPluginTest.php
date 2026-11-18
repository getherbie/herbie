<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
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
use Psr\SimpleCache\CacheInterface;
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

    public function testCsrfTokenExpiresAfterFifteenMinutes(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');

        $recent = time() - 899;
        $expired = time() - 901;

        $this->assertTrue($method->invoke($plugin, [
            'csrf_token' => $recent . ':' . hash_hmac('sha256', (string)$recent, 'test-secret'),
        ]));
        $this->assertFalse($method->invoke($plugin, [
            'csrf_token' => $expired . ':' . hash_hmac('sha256', (string)$expired, 'test-secret'),
        ]));
    }

    public function testFutureCsrfTokenIsRejected(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');

        $future = time() + 10;

        $this->assertFalse($method->invoke($plugin, [
            'csrf_token' => $future . ':' . hash_hmac('sha256', (string)$future, 'test-secret'),
        ]));
    }

    public function testCsrfSecretIsRequired(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => ['csrf' => ['secret' => '']]],
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A non-empty plugins.contact.csrf.secret must be configured.');
        $this->invokePrivate($plugin, 'getCsrfSecret', []);
    }

    public function testCsrfSecretPlaceholderIsRejected(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => ['csrf' => ['secret' => '<plugin_contact_csrf_secret>']]],
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A non-empty plugins.contact.csrf.secret must be configured.');
        $this->invokePrivate($plugin, 'getCsrfSecret', []);
    }

    public function testCsrfSecretReturnsConfiguredValue(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => ['csrf' => ['secret' => 'configured-secret']]],
        ]));

        $this->assertSame('configured-secret', $this->invokePrivate($plugin, 'getCsrfSecret', []));
    }

    public function testCaptchaSecretIsRequired(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => ['captcha' => ['secret' => '']]],
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A non-empty plugins.contact.captcha.secret must be configured.');
        $this->invokePrivate($plugin, 'getCaptchaSecret', []);
    }

    public function testCaptchaSecretPlaceholderIsRejected(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => ['captcha' => ['secret' => '<plugin_contact_captcha_secret>']]],
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A non-empty plugins.contact.captcha.secret must be configured.');
        $this->invokePrivate($plugin, 'getCaptchaSecret', []);
    }

    public function testCaptchaSecretReturnsConfiguredValue(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => ['captcha' => ['secret' => 'configured-captcha-secret']]],
        ]));

        $this->assertSame('configured-captcha-secret', $this->invokePrivate($plugin, 'getCaptchaSecret', []));
    }

    public function testCreatedChallengeIsSignedWithPbkdf2AndExpires(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $data = json_decode($this->invokePrivate($plugin, 'createAltchaChallenge', []), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('parameters', $data);
        $this->assertArrayHasKey('signature', $data);
        $this->assertSame('PBKDF2/SHA-256', $data['parameters']['algorithm']);
        $this->assertGreaterThan(time(), $data['parameters']['expiresAt']);
    }

    public function testValidAltchaPayloadIsAccepted(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $payload = $this->solveChallengeForPlugin();

        $this->assertTrue($this->invokePrivate($plugin, 'isValidAltchaPayload', [['altcha' => $payload]]));
    }

    public function testMissingAltchaPayloadIsRejected(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertFalse($this->invokePrivate($plugin, 'isValidAltchaPayload', [[]]));
        $this->assertFalse($this->invokePrivate($plugin, 'isValidAltchaPayload', [['altcha' => '']]));
        $this->assertFalse($this->invokePrivate($plugin, 'isValidAltchaPayload', [['altcha' => ['not-a-string']]]));
    }

    public function testMalformedAltchaPayloadIsRejected(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertFalse($this->invokePrivate($plugin, 'isValidAltchaPayload', [['altcha' => 'not-valid-base64!']]));
    }

    public function testTamperedAltchaPayloadIsRejected(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $payload = Payload::fromBase64($this->solveChallengeForPlugin());

        $tampered = new Payload(
            $payload->challenge,
            new \AltchaOrg\Altcha\Solution($payload->solution->counter, str_repeat('0', 64)),
        );

        $this->assertFalse($this->invokePrivate($plugin, 'isValidAltchaPayload', [['altcha' => $tampered->toBase64()]]));
    }

    public function testExpiredAltchaPayloadIsRejected(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $payload = $this->solveChallengeForPlugin(time() - 10);

        $this->assertFalse($this->invokePrivate($plugin, 'isValidAltchaPayload', [['altcha' => $payload]]));
    }

    private function solveChallengeForPlugin(?int $expiresAt = null): string
    {
        $secret = 'test-captcha-secret';
        $altcha = new Altcha($secret, $secret);
        $challenge = $altcha->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2(),
            cost: 100,
            counter: 1,
            expiresAt: $expiresAt ?? time() + 600,
        ));
        $solution = $altcha->solveChallenge(new SolveChallengeOptions(
            challenge: $challenge,
            algorithm: new Pbkdf2(),
        ));

        $this->assertNotNull($solution);
        return (new Payload($challenge, $solution))->toBase64();
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
            'phone' => '<i>callme</i>',
        ]);

        $this->assertSame('  John  ', $filtered['name']);
        $this->assertSame('Foo@Bar.com', $filtered['email']);
        $this->assertSame('Hello x world', $filtered['message']);
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

    public function testHoneypotFieldsAreSilentlyAccepted(): void
    {
        $plugin = $this->createUninitializedPlugin();

        // sendEmail() short-circuits before SMTP when a honeypot is filled.
        // An invalid email address would otherwise make it return false.
        foreach (['phone' => '123', 'website' => 'https://spam.example'] as $field => $value) {
            $data = ['name' => '', 'email' => 'invalid', 'message' => '', $field => $value];
            $this->assertTrue($this->invokePrivate($plugin, 'sendEmail', [$data, 'me@example.com']));
        }
    }

    public function testHoneypotFieldNamesAreConfigurable(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => [
                'csrf' => ['secret' => 'test-secret'],
                'honeypots' => ['company', 'email', 'company', ''],
            ]],
        ]));

        $filtered = $this->invokePrivate($plugin, 'filterFormData', [[
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'Hello',
            'company' => '<i>Acme</i>',
            'phone' => '123',
        ]]);

        $this->assertSame('Acme', $filtered['company']);
        $this->assertArrayNotHasKey('phone', $filtered);

        // The configured name triggers the honeypot, an old default name does not.
        $this->assertTrue($this->invokePrivate($plugin, 'sendEmail', [
            ['name' => '', 'email' => 'invalid', 'message' => '', 'company' => 'Acme'],
            'me@example.com',
        ]));
        $this->assertFalse($this->invokePrivate($plugin, 'sendEmail', [
            ['name' => '', 'email' => 'invalid', 'message' => '', 'phone' => '123'],
            'me@example.com',
        ]));
    }

    public function testHumanTimingRequiresPlausibleFormAge(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $now = time();

        $this->assertFalse($this->invokePrivate($plugin, 'isHumanTiming', [['form_time' => (string)$now]]));
        $this->assertFalse($this->invokePrivate($plugin, 'isHumanTiming', [['form_time' => '0']]));
        $this->assertFalse($this->invokePrivate($plugin, 'isHumanTiming', [['form_time' => (string)($now - 901)]]));
        $this->assertTrue($this->invokePrivate($plugin, 'isHumanTiming', [['form_time' => (string)($now - 10)]]));
    }

    public function testRateLimitIsSkippedWithoutCache(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertTrue($this->invokePrivate($plugin, 'checkRateLimit', []));
    }

    public function testRateLimitAllowsFiveRequestsPerHourPerIp(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $cache = new ArrayCacheStub();
        $this->setPrivateProperty($plugin, 'cache', $cache);

        $previousIp = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';

        try {
            for ($i = 0; $i < 5; $i++) {
                $this->assertTrue($this->invokePrivate($plugin, 'checkRateLimit', []));
            }
            $this->assertFalse($this->invokePrivate($plugin, 'checkRateLimit', []));
            $this->assertSame(3600, $cache->ttls['contact_rate_' . md5('203.0.113.10')]);
        } finally {
            if ($previousIp === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $previousIp;
            }
        }
    }

    public function testConfiguredWindowsAreClamped(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');

        // Upper bound: 86400 is clamped to 3600, so a 2h-old token is rejected.
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => [
                'csrf' => ['secret' => 'test-secret', 'tokenTtlSeconds' => 86400],
            ]],
        ]));
        $old = time() - 7200;
        $this->assertFalse($method->invoke($plugin, [
            'csrf_token' => $old . ':' . hash_hmac('sha256', (string)$old, 'test-secret'),
        ]));

        // Lower bounds: 1 is clamped to 60 for both token TTL and max form age.
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => [
                'csrf' => ['secret' => 'test-secret', 'tokenTtlSeconds' => 1],
                'form' => ['minAgeSeconds' => 0, 'maxAgeSeconds' => 1],
            ]],
        ]));
        $recent = time() - 30;
        $this->assertTrue($method->invoke($plugin, [
            'csrf_token' => $recent . ':' . hash_hmac('sha256', (string)$recent, 'test-secret'),
        ]));
        $this->assertTrue($this->invokePrivate($plugin, 'isHumanTiming', [['form_time' => (string)$recent]]));

        // Form min age upper bound: 999 is clamped to 60, so a 30s-old form is rejected.
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => [
                'csrf' => ['secret' => 'test-secret'],
                'form' => ['minAgeSeconds' => 999],
            ]],
        ]));
        $this->assertFalse($this->invokePrivate($plugin, 'isHumanTiming', [['form_time' => (string)$recent]]));
    }

    public function testRateLimitConfigIsClamped(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $cache = new ArrayCacheStub();
        $this->setPrivateProperty($plugin, 'cache', $cache);
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => [
                'csrf' => ['secret' => 'test-secret'],
                'rateLimit' => ['maxRequests' => 0, 'windowSeconds' => 1],
            ]],
        ]));

        $previousIp = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '203.0.113.11';

        try {
            $this->assertTrue($this->invokePrivate($plugin, 'checkRateLimit', []));
            $this->assertFalse($this->invokePrivate($plugin, 'checkRateLimit', []));
            $this->assertSame(60, $cache->ttls['contact_rate_' . md5('203.0.113.11')]);
        } finally {
            if ($previousIp === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $previousIp;
            }
        }
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
        $this->setPrivateProperty($plugin, 'config', new Config(['plugins' => ['contact' => ['csrf' => ['secret' => 'test-secret']]]]));

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
        $this->setPrivateProperty($plugin, 'config', new Config(['plugins' => ['contact' => [
            'csrf' => ['secret' => 'test-secret'],
            'captcha' => ['secret' => 'test-captcha-secret'],
        ]]]));
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

final class ArrayCacheStub implements CacheInterface
{
    public array $values = [];
    public array $ttls = [];

    #[\Override]
    public function get($key, $default = null)
    {
        return $this->values[$key] ?? $default;
    }

    #[\Override]
    public function set($key, $value, $ttl = null): bool
    {
        $this->values[$key] = $value;
        $this->ttls[$key] = $ttl;
        return true;
    }

    #[\Override]
    public function delete($key): bool
    {
        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        return true;
    }

    #[\Override]
    public function getMultiple($keys, $default = null): iterable
    {
        return [];
    }

    #[\Override]
    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    #[\Override]
    public function deleteMultiple($keys): bool
    {
        return true;
    }

    #[\Override]
    public function has($key): bool
    {
        return isset($this->values[$key]);
    }
}
