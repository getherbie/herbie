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

class Plugin
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->init();
    }

    /**
     * Initializer
     */
    public function init()
    {
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        $methods = get_class_methods(get_called_class());

        $list = [];
        foreach ($methods as $method) {
            if (strpos($method, 'on') === 0) {
                $list[] = $method;
            }
        }

        return $list;
    }

    /**
     * @param array $htmlOptions
     * @return string
     */
    protected function buildHtmlAttributes($htmlOptions = [])
    {
        $attributes = '';
        foreach ($htmlOptions as $key => $value) {
            $attributes .= $key . '="' . $value . '" ';
        }
        return trim($attributes);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function config($name, $default = null)
    {
        return $this->config->get($name, $default);
    }

    protected function render($name, array $context = [])
    {
        return Application::getService('Twig')->render($name, $context);
    }

    /**
     * @param string $service
     * @return mixed
     */
    protected function getService($service)
    {
        return Application::getService($service);
    }

}
