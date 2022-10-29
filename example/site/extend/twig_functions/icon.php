<?php

declare(strict_types=1);

namespace example\site\extend\twig_functions;

use Twig\TwigFunction;

use function herbie\file_read;

return new TwigFunction('icon', function (string $name, ?int $width = null, ?int $height = null, ?string $fill = null) {
    $template = dirname(__DIR__, 2) . '/themes/default/icons/{icon}.svg';
    $filepath = str_replace('{icon}', $name, $template);
    if (!is_file($filepath)) {
        return '';
    }
    $svg = file_read($filepath);

    if ($width > 0) {
        $svg = str_replace('width="16"', 'width="' . $width . '"', $svg);
    }
    if (!empty($height)) {
        $svg = str_replace('height="16"', 'height="' . $height . '"', $svg);
    }
    if (!empty($fill)) {
        $svg = str_replace('fill="currentColor"', 'fill="' . $fill . '"', $svg);
    }

    return $svg;
}, ['is_safe' => ['all']]);
