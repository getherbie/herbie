<?php

use Twig\TwigTest;

return new TwigTest('odd', function ($value) {
    return ($value % 2) != 0;
});
