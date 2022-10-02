<?php

use Twig\TwigFunction;

return new TwigFunction('hello', function ($name) {
    return "Hello {$name}!";
});
