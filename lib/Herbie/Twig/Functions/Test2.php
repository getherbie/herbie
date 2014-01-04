<?php

if(!isset($this) || (!$this instanceof HerbieExtension)) {
}

return new \Twig_SimpleFunction('test2', function() {
    return 'TEST2 for external function';
}, ['is_safe' => ['html']]);
