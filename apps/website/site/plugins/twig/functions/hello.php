<?php

return new Twig_SimpleFunction('hello', function ($name) {
    return "Hallo {$name}!";
});
