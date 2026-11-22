<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use Codeception\Test\Unit;
use DateInterval;
use herbie\Application;
use herbie\Config;
use herbie\events\ResponseGeneratedEvent;
use herbie\Page;
use herbie\Translator;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\plugins\contact\ContactPlugin;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use herbie\UrlManager;
use PHPMailer\PHPMailer\PHPMailer;
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
        $parts = explode(':', $token);
        $this->assertCount(3, $parts);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $parts[1]);

        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');
        $withCookie = $this->createPluginWithCsrfCookie($parts[1]);

        $this->assertTrue($method->invoke($withCookie, ['csrf_token' => $token]));
        $this->assertFalse($method->invoke($plugin, ['csrf_token' => $token]));
        $this->assertFalse($method->invoke($withCookie, ['csrf_token' => 'invalid']));
        $this->assertFalse($method->invoke($withCookie, ['csrf_token' => '']));
    }

    public function testCsrfTokenBindsToVisitorCookie(): void
    {
        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');
        $visitorA = $this->createPluginWithCsrfCookie(str_repeat('a', 32));
        $visitorB = $this->createPluginWithCsrfCookie(str_repeat('b', 32));
        $token = $this->createCsrfToken(str_repeat('a', 32));

        $this->assertTrue($method->invoke($visitorA, ['csrf_token' => $token]));
        $this->assertFalse($method->invoke($visitorB, ['csrf_token' => $token]));
    }

    public function testCsrfTokenReusesValidVisitorCookie(): void
    {
        $nonce = str_repeat('c', 32);
        $plugin = $this->createPluginWithCsrfCookie($nonce);

        $parts = explode(':', $plugin->csrfToken());
        $this->assertSame($nonce, $parts[1]);
    }

    public function testCsrfCookieIsAddedToResponse(): void
    {
        $plugin = $this->createUninitializedPlugin();
        [$tokenTimestamp, $nonce] = explode(':', $plugin->csrfToken());
        $event = new ResponseGeneratedEvent((new ResponseFactory())->createResponse());

        $plugin->onResponseGenerated($event);
        $cookie = $event->getResponse()->getHeaderLine('Set-Cookie');
        preg_match('/herbie_contact_csrf=([^:]+):([^:]+):/', $cookie, $matches);

        $this->assertSame($tokenTimestamp, $matches[1] ?? null);
        $this->assertSame($nonce, $matches[2] ?? null);
        $this->assertStringContainsString('Path=/', $cookie);
        $this->assertStringContainsString('Max-Age=900', $cookie);
        $this->assertStringContainsString('HttpOnly', $cookie);
        $this->assertStringContainsString('SameSite=Lax', $cookie);
        $this->assertStringContainsString('Secure', $cookie);
    }

    public function testCsrfTokenExpiresAfterFifteenMinutes(): void
    {
        $plugin = $this->createPluginWithCsrfCookie(str_repeat('a', 32));
        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');

        $this->assertTrue($method->invoke($plugin, [
            'csrf_token' => $this->createCsrfToken(str_repeat('a', 32), time() - 899),
        ]));
        $this->assertFalse($method->invoke($plugin, [
            'csrf_token' => $this->createCsrfToken(str_repeat('a', 32), time() - 901),
        ]));
    }

    public function testFutureCsrfTokenIsRejected(): void
    {
        $plugin = $this->createPluginWithCsrfCookie(str_repeat('a', 32));
        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');

        $this->assertFalse($method->invoke($plugin, [
            'csrf_token' => $this->createCsrfToken(str_repeat('a', 32), time() + 10),
        ]));
    }

    public function testExpiredCsrfCookieIsRejected(): void
    {
        $plugin = $this->createPluginWithCsrfCookie(str_repeat('d', 32), time() - 901);
        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');

        $this->assertFalse($method->invoke($plugin, [
            'csrf_token' => $this->createCsrfToken(str_repeat('d', 32)),
        ]));
    }

    public function testCsrfSecretRotationInvalidatesOutstandingCookieAndToken(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $nonce = str_repeat('e', 32);
        $timestamp = time();
        $oldCookie = $timestamp . ':' . $nonce . ':' . hash_hmac(
            'sha256',
            'cookie:' . $timestamp . ':' . $nonce,
            'old-secret',
        );
        $this->setPrivateProperty(
            $plugin,
            'request',
            (new ServerRequest())
                ->withUri(new Uri('https://example.com/contact'))
                ->withCookieParams(['herbie_contact_csrf' => $oldCookie]),
        );
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => ['csrf' => ['secret' => 'new-secret']]],
        ]));
        $oldToken = $timestamp . ':' . $nonce . ':' . hash_hmac(
            'sha256',
            'token:' . $timestamp . ':' . $nonce,
            'old-secret',
        );
        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');

        $this->assertFalse($method->invoke($plugin, ['csrf_token' => $oldToken]));
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
        $this->assertSame(5000, $data['parameters']['cost']);
        $this->assertGreaterThan(time() + 890, $data['parameters']['expiresAt']);
        $this->assertLessThan(time() + 910, $data['parameters']['expiresAt']);
    }

    public function testCaptchaCostAndTtlAreConfigurableAndClamped(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => ['captcha' => [
                'secret' => 'test-captcha-secret',
                'cost' => 1,
                'ttlSeconds' => 1,
            ]]],
        ]));
        $data = json_decode($this->invokePrivate($plugin, 'createAltchaChallenge', []), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1000, $data['parameters']['cost']);
        $this->assertGreaterThan(time() + 50, $data['parameters']['expiresAt']);
        $this->assertLessThan(time() + 70, $data['parameters']['expiresAt']);

        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => ['captcha' => [
                'secret' => 'test-captcha-secret',
                'cost' => 999999,
                'ttlSeconds' => 99999,
            ]]],
        ]));
        $data = json_decode($this->invokePrivate($plugin, 'createAltchaChallenge', []), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(100000, $data['parameters']['cost']);
        $this->assertGreaterThan(time() + 3590, $data['parameters']['expiresAt']);
        $this->assertLessThan(time() + 3610, $data['parameters']['expiresAt']);
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

    public function testCopyFieldIsFilteredAndReserved(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $checked = $this->invokePrivate($plugin, 'filterFormData', [[
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'Hello',
            'send_copy' => '1',
        ]]);
        $this->assertTrue($checked['send_copy']);

        $unchecked = $this->invokePrivate($plugin, 'filterFormData', [[
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'Hello',
        ]]);
        $this->assertFalse($unchecked['send_copy']);

        // Real input fields cannot be configured as honeypots.
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => [
                'csrf' => ['secret' => 'test-secret'],
                'honeypots' => ['send_copy', 'altcha'],
            ]],
        ]));
        $this->assertSame(['phone', 'website'], $this->invokePrivate($plugin, 'getHoneypotFields', []));
    }

    public function testSenderCopyRequiresOptInAndValidAddress(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $data = ['send_copy' => true, 'email' => 'john@example.com'];

        // Disabled by default.
        $this->assertFalse($this->invokePrivate($plugin, 'shouldSendCopy', [$data]));

        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => [
                'csrf' => ['secret' => 'test-secret'],
                'copy' => ['enabled' => true],
            ]],
        ]));

        $this->assertTrue($this->invokePrivate($plugin, 'shouldSendCopy', [$data]));
        $this->assertFalse($this->invokePrivate($plugin, 'shouldSendCopy', [['email' => 'john@example.com']]));
        $this->assertFalse($this->invokePrivate($plugin, 'shouldSendCopy', [[
            'send_copy' => true,
            'email' => 'not-an-email',
        ]]));
    }

    public function testValidateFormDataReturnsErrors(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $method = new ReflectionMethod(ContactPlugin::class, 'validateFormData');

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

    public function testMailBodiesArePlainText(): void
    {
        $plugin = $this->createUninitializedPlugin();
        $mail = new PHPMailer(true);
        $body = "Hallo Thomas\n\nLG\nHans";

        $this->invokePrivate($plugin, 'setPlainTextBody', [$mail, $body]);

        $this->assertSame(PHPMailer::CONTENT_TYPE_PLAINTEXT, $mail->ContentType);
        $this->assertFalse($mail->alternativeExists());
        $this->assertSame('', $mail->AltBody);
        $this->assertSame($body, $mail->Body);
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
                'honeypots' => ['company', 'email', 'button', 'bad name', 'company', ''],
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
        $this->assertSame(['company'], $this->invokePrivate($plugin, 'getHoneypotFields', []));

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
        $nonce = str_repeat('a', 32);
        $plugin = $this->createPluginWithCsrfCookie($nonce);
        $method = new ReflectionMethod(ContactPlugin::class, 'isValidCsrfToken');

        // Upper bound: 86400 is clamped to 3600, so a 2h-old token is rejected.
        $this->setPrivateProperty($plugin, 'config', new Config([
            'plugins' => ['contact' => [
                'csrf' => ['secret' => 'test-secret', 'tokenTtlSeconds' => 86400],
            ]],
        ]));
        $old = time() - 7200;
        $this->assertFalse($method->invoke($plugin, [
            'csrf_token' => $this->createCsrfToken($nonce, $old),
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
            'csrf_token' => $this->createCsrfToken($nonce, $recent),
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

    private function createPluginWithCsrfCookie(string $nonce, ?int $timestamp = null): ContactPlugin
    {
        $plugin = $this->createUninitializedPlugin();
        $timestamp ??= time();
        $signature = hash_hmac('sha256', 'cookie:' . $timestamp . ':' . $nonce, 'test-secret');
        $this->setPrivateProperty(
            $plugin,
            'request',
            (new ServerRequest())
                ->withUri(new Uri('https://example.com/contact'))
                ->withCookieParams(['herbie_contact_csrf' => $timestamp . ':' . $nonce . ':' . $signature]),
        );

        return $plugin;
    }

    private function createCsrfToken(string $nonce, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $signature = hash_hmac('sha256', 'token:' . $timestamp . ':' . $nonce, 'test-secret');
        return $timestamp . ':' . $nonce . ':' . $signature;
    }

    private function createUninitializedPlugin(): ContactPlugin
    {
        $plugin = (new ReflectionClass(ContactPlugin::class))->newInstanceWithoutConstructor();

        $this->setPrivateProperty($plugin, 'responseFactory', new ResponseFactory());
        $this->setPrivateProperty(
            $plugin,
            'request',
            (new ServerRequest())->withUri(new Uri('https://example.com/contact')),
        );
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
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    #[\Override]
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $this->values[$key] = $value;
        $this->ttls[$key] = $ttl;
        return true;
    }

    #[\Override]
    public function delete(string $key): bool
    {
        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        return true;
    }

    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return [];
    }

    #[\Override]
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        return true;
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    #[\Override]
    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }
}
