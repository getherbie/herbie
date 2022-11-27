<?php

declare(strict_types=1);

namespace website\site\extend\twig_functions;

use Exception;

const CACHE_FILE = __DIR__ . '/git_commits.cache';

return ['git_commits', function () {

    $commits_from_cache = function (): ?string {
        $exists = file_exists(CACHE_FILE);
        if (!$exists || (time() > strtotime('+2 hours', filemtime(CACHE_FILE)))) {
            try {
                $data = implode(file('https://github.com/getherbie/herbie/commits/2.x-develop.atom'));
            } catch (Exception $e) {
                return null;
            }
            file_put_contents(CACHE_FILE, $data);
        } else {
            $data = file_get_contents(CACHE_FILE);
        }
        return $data;
    };

    $commits = $commits_from_cache();
    if ($commits === null) {
        return '';
    }
    $xml = simplexml_load_string($commits);
    $markup = '<div class="gc">';
    $markup .= sprintf('<h4 class="gc-header">%s</h4>', 'GitHub Activity');
    $markup .= sprintf('<div class="gc-subheader">%s</div>', $xml->title);
    $i = 0;
    foreach ($xml->entry as $entry) {
        $markup .= '<div class="gc-entry">';
        $markup .= sprintf(
            '<div class="gc-entry-title"><a href="%s">%s</a></div>',
            $entry->link['href'],
            $entry->title
        );
        $markup .= '<div class="gc-entry-text">';
        $markup .= sprintf(
            '<span class="gc-entry-date">%s</span> by <a class="gc-entry-author" href="%s">%s</a>',
            date('d.m.Y', strtotime((string)$entry->updated)),
            $entry->author->uri,
            $entry->author->name
        );
        $markup .= '</div>';
        $markup .= '</div>';
        if ($i++ >= 9) {
            break;
        }
    }
    $markup .= '</div>';
    return $markup;
}, ['is_safe' => ['all']]];
