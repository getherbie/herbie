<?php

declare(strict_types=1);

namespace herbie;

use InvalidArgumentException;

/**
 * A minimal, file-based session store for Herbie.
 *
 * Herbie is a stateless, flat-file CMS. This class provides a small,
 * self-contained session implementation that stores session data as a
 * single JSON file inside the site's runtime directory. It is intentionally
 * not wired into the core request lifecycle: plugins that need session
 * support (e.g. the contact plugin for CSRF protection) opt in by creating
 * an instance and calling start().
 *
 * The design mirrors FileCache: one JSON file per session, a TTL, and a
 * randomized garbage collection of expired files.
 */
final class Session
{
    /** @var array<string, mixed> */
    private array $data = [];
    private string $path;
    private string $name;
    private int $ttl;
    private bool $active = false;

    public function __construct(string $path, string $name = 'herbie_sid', int $ttl = 3600)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if ($path === '' || !is_dir($path) && !@mkdir($path, 0755, true) || !is_dir($path)) {
            throw new InvalidArgumentException('Session path is not a valid directory: ' . $path);
        }

        if (!is_writable($path)) {
            throw new InvalidArgumentException('Session path is not writable: ' . $path);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('Session cookie name must be alphanumeric or underscore');
        }

        $this->path = $path;
        $this->name = $name;
        $this->ttl = max(60, $ttl);
    }

    /**
     * Load or create the session and send the session cookie.
     */
    public function start(): void
    {
        if ($this->active || PHP_SAPI === 'cli') {
            return;
        }

        $this->garbageCollect();

        $sid = $this->readSidFromCookie();
        if ($sid === null) {
            $sid = $this->generateSid();
        }

        $this->data = $this->load($sid);

        $this->sendCookie($sid);

        $this->active = true;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getId(): ?string
    {
        return $this->active ? $this->readSidFromCookie() : null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Persist the session data to disk.
     */
    public function save(): void
    {
        if (!$this->active) {
            return;
        }

        $sid = $this->readSidFromCookie();
        if ($sid === null) {
            return;
        }

        if ($this->data === []) {
            $this->delete($sid);
            return;
        }

        $this->write($sid, $this->data);
    }

    /**
     * Generate a new CSRF token, store it in the session and return it.
     */
    public function generateCsrfToken(): string
    {
        $this->ensureStarted();

        $token = bin2hex(random_bytes(32));
        $this->set('csrf_token', $token);

        return $token;
    }

    /**
     * Verify the submitted CSRF token against the one stored in the session.
     */
    public function validateCsrfToken(string $token): bool
    {
        $this->ensureStarted();

        if ($token === '' || !is_string($token)) {
            return false;
        }

        $expected = (string)$this->get('csrf_token', '');

        if ($expected === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    private function ensureStarted(): void
    {
        if (!$this->active) {
            $this->start();
        }
    }

    private function generateSid(): string
    {
        return bin2hex(random_bytes(24));
    }

    private function readSidFromCookie(): ?string
    {
        if (PHP_SAPI === 'cli') {
            return $_COOKIE[$this->name] ?? null;
        }

        $sid = $_COOKIE[$this->name] ?? null;

        if (!is_string($sid) || !preg_match('/^[a-f0-9]{48}$/', $sid)) {
            return null;
        }

        return $sid;
    }

    private function sendCookie(string $sid): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        setcookie($this->name, $sid, [
            'expires' => time() + $this->ttl,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[$this->name] = $sid;
    }

    /** @return array<string, mixed> */
    private function load(string $sid): array
    {
        $file = $this->getFilename($sid);

        if (!file_exists($file)) {
            return [];
        }

        $contents = (string)file_get_contents($file);

        if (!json_validate($contents)) {
            $this->delete($sid);

            return [];
        }

        $session = json_decode($contents, true);

        if (!is_array($session) || ($session['sid'] ?? null) !== $sid) {
            $this->delete($sid);

            return [];
        }

        if (($session['expires'] ?? 0) < time()) {
            $this->delete($sid);

            return [];
        }

        $data = $session['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $data */
    private function write(string $sid, array $data): void
    {
        $payload = [
            'sid' => $sid,
            'data' => $data,
            'ctime' => time(),
            'expires' => time() + $this->ttl,
        ];

        file_put_contents(
            $this->getFilename($sid),
            json_encode($payload),
            LOCK_EX,
        );
    }

    private function delete(string $sid): void
    {
        $file = $this->getFilename($sid);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function getFilename(string $sid): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $sid . '.session';
    }

    /**
     * Remove expired session files. Runs with a small probability to avoid
     * the cost on every request.
     */
    private function garbageCollect(): void
    {
        // ~10% chance of running GC on any given request.
        if (random_int(1, 100) > 10) {
            return;
        }

        $files = glob($this->path . DIRECTORY_SEPARATOR . '*.session');
        if ($files === false) {
            return;
        }

        $now = time();
        foreach ($files as $file) {
            $contents = (string)@file_get_contents($file);
            if ($contents === '' || !json_validate($contents)) {
                @unlink($file);
                continue;
            }

            $session = json_decode($contents, true);
            if (!is_array($session) || ($session['expires'] ?? 0) < $now) {
                @unlink($file);
            }
        }
    }
}
