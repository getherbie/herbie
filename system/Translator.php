<?php

declare(strict_types=1);

namespace herbie;

final class Translator
{
    private string $language;

    /**
     * @var array<string, string[]>
     */
    private array $paths;

    /**
     * @var array<string, array<string, array<string, string>>>
     */
    private array $messages;

    /**
     * Translator constructor.
     */
    public function __construct(string $language)
    {
        $this->language = $language;
        $this->paths = [];
        $this->messages = [];
    }

    /**
     * Initializer
     */
    public function init(): self
    {
        $this->loadMessages();
        return $this;
    }

    /**
     * @param array<string, string> $params
     */
    public function t(string $category, string $message, array $params = []): string
    {
        return $this->translate($category, $message, $params);
    }

    /**
     * @param array<string, string> $params
     */
    public function translate(string $category, string $message, array $params = []): string
    {
        if (isset($this->messages[$this->language][$category][$message])) {
            $message = $this->messages[$this->language][$category][$message];
        }
        if (empty($params)) {
            return $message;
        }
        return $this->replacePlaceholders($message, $params);
    }

    /**
     * @param array<string, string> $params
     */
    private function replacePlaceholders(string $message, array $params): string
    {
        $paramsWithBrackets = [];
        foreach ($params as $key => $value) {
            $key = '{' . $key . '}';
            $paramsWithBrackets[$key] = $value;
        }
        return strtr($message, $paramsWithBrackets);
    }

    private function loadMessages(): void
    {
        foreach ($this->paths as $category => $paths) {
            foreach ($paths as $path) {
                $messagePath = sprintf('%s/%s.php', $path, $this->language);
                if (file_exists($messagePath)) {
                    // NOTE this must be "require" here, not only "require_once"
                    $this->messages[$this->language][$category] = require $messagePath;
                }
            }
        }
    }

    /**
     * @param string[]|string $path
     */
    public function addPath(string $category, $path): void
    {
        if (!isset($this->paths[$category])) {
            $this->paths[$category] = [];
        }
        if (is_string($path)) {
            $path = [$path];
        } elseif (!is_array($path)) {
            $message = sprintf('Argument $path has to be an array or a string, %s given.', gettype($path));
            throw new \InvalidArgumentException($message);
        }
        $this->paths[$category] = array_merge($this->paths[$category], $path);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
