<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

class Translator
{
    /**
     * @var string
     */
    protected $language;

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var array
     */
    protected $messages;

    /**
     * @param string $language
     * @param array $paths
     */
    public function __construct(string $language, array $paths = [])
    {
        $this->language = $language;
        $this->paths = [];
        $this->messages = [];
        foreach ($paths as $key => $path) {
            $this->addPath($key, $path);
        }
    }

    /**
     * Initializer
     */
    public function init(): void
    {
        $this->loadMessages();
    }

    /**
     * @param string $category
     * @param string $message
     * @param array $params
     * @return string
     */
    public function t(string $category, string $message, array $params = []): string
    {
        return $this->translate($category, $message, $params);
    }

    /**
     * @param string $category
     * @param string $message
     * @param array $params
     * @return string
     */
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

    /**
     * @return void
     */
    protected function loadMessages(): void
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

    /**
     * @param string $category
     * @param string $path
     */
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
