<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class Translator
{
    private string $language;

    private array $paths;

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
    public function init(): void
    {
        $this->loadMessages();
    }

    public function t(string $category, string $message, array $params = []): string
    {
        return $this->translate($category, $message, $params);
    }

    public function translate(string $category, string $message, array $params = []): string
    {
        if (isset($this->messages[$this->language][$category][$message])) {
            $message = $this->messages[$this->language][$category][$message];
        }
        if (empty($params)) {
            return $message;
        }
        return strtr($message, $params);
    }

    private function loadMessages(): void
    {
        foreach ($this->paths as $category => $paths) {
            foreach ($paths as $path) {
                $messagePath = sprintf('%s/%s.php', $path, $this->language);
                if (file_exists($messagePath)) {
                    $this->messages[$this->language][$category] = require_once($messagePath);
                }
            }
        }
    }

    public function addPath(string $category, string $path): void
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
}
