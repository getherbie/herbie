<?php

namespace herbie\plugin\test\classes;

class TestExtension extends \Twig_Extension
{
    /**
     * @var \Herbie\Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'test';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        $options = ['is_safe' => ['html']];
        return [
            new \Twig_SimpleFunction('test', function() {
                $this->app['assets']->addCss('@plugin/test/assets/test.css');
                echo "TEST.";
            }, $options),
        ];
    }
}
