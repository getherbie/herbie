<?php

$test = 'blog/(?<year>[a-zA-Z0-9\_\-]+)/(?<month>[a-zA-Z0-9\_\-]+)/(?<day>[a-zA-Z0-9\_\-]+)';
$route = 'blog/{year}/{month}/{day}';

$replacements = [
    'year' => '[0-9]{4}',
    'month' => '[0-9]{2}',
    'day' => '[0-9]{2}'
];

$string = preg_replace_callback('/{([a-zA-Z0-9\_\-]+)}/', function($matches) use ($replacements) {
    if (count($matches) === 2) {
        $name = $matches[1];
        if (empty($replacements[$name])) {
            return "(?<" . $name . ">[a-zA-Z0-9\_\-]+)";
        }
        return "(?<" . $name . ">" . $replacements[$name] . ")";
    }
    return '';
}, $route);

echo "<pre>";
echo htmlentities('@^blog/(?<year>[a-zA-Z0-9\_\-]+)/(?<month>[a-zA-Z0-9\_\-]+)/(?<day>[a-zA-Z0-9\_\-]+)$@D');
echo "<br>";
echo htmlentities($string);
echo "</pre>";
