<?php

return new Twig_SimpleTest('odd', function ($value) {
    return ($value % 2) != 0;
});
