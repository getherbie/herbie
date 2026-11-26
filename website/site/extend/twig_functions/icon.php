<?php

declare(strict_types=1);

namespace herbie\website\site\extend\twig_functions;

use Twig\TwigFunction;

use function herbie\file_read;

// Site functions are included in LocalExtensionsPlugin method scope, so $this->config is available here.
$themesPath = trim($this->config->getAsString('paths.themes'));
$theme = trim($this->config->getAsString('theme'));
$themes = [$theme];
$themeParent = trim($this->config->getAsString('themeParent'));
if ($themeParent !== '' && $themeParent !== $theme && is_dir($themesPath . '/' . $themeParent)) {
    $themes[] = $themeParent;
}
$templates = [];
foreach ($themes as $candidate) {
    $templates[] = $themesPath . '/' . $candidate . '/icons/{icon}.svg';
}

return new TwigFunction('icon', function (string $name, ?int $width = null, ?int $height = null, ?string $fill = null) use ($templates): string|array {
    foreach ($templates as $template) {
        $filepath = str_replace('{icon}', $name, $template);
        if (!is_file($filepath)) {
            continue;
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
    }
    return '';
}, ['is_safe' => ['all']]);
