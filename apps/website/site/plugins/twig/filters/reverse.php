<?php

return new Twig_SimpleFilter('reverse', function ($string) {
    return strrev($string);
});
