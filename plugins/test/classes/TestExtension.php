<?php

namespace herbie\plugin\test\classes;

class TestExtension extends \Twig_Extension
{
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
                echo "XXX-YYY-ZZZ";
            }, $options),
        ];
    }
}
