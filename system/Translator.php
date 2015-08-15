<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

class Translator
{
    /**
     * @var string
     */
    private $language;

    /**
     * @var array
     */
    private $paths;

    /**
     * @var array
     */
    private $messages;

    /**
     * @param string $language
     * @param array $paths
     */
    public function __construct($language, array $paths = [])
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
    public function init()
    {
        $this->loadMessages();
    }

    /**
     * @param string $category
     * @param string $message
     * @param array $params
     * @return string
     */
    public function t($category, $message, array $params = [])
    {
        return $this->translate($category, $message, $params);
    }

    /**
     * @param string $category
     * @param string $message
     * @param array $params
     * @return string
     */
    public function translate($category, $message, array $params = [])
    {
        if(isset($this->messages[$this->language][$category][$message])) {
            $message = $this->messages[$this->language][$category][$message];
        }
        if(empty($params)) {
            return $message;
        }
        return strtr($message, $params);
    }

    /**
     * @return void
     */
    private function loadMessages()
    {
        foreach($this->paths as $category => $paths) {
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
     * @param string|array $paths
     */
    public function addPath($category, $path)
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
