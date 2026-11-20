---
title: Grid System
layout: doc
---

# Grid System

The Grid System plugin turns markers in the page content into a configurable HTML grid.
It demonstrates an **event listener with a higher priority**, a regex replacement and nested configuration.

## Structure

~~~text
site/extend/plugins/grid/
├── config.php
└── GridPlugin.php
~~~

## Configuration defaults

~~~php
<?php

return [
    'templates' => [
        'two' => [
            'row' => '<div class="pure-g">{row}</div>',
            'cols' => [
                '<div class="pure-u-1 pure-u-md-1-2">{col}</div>',
                '<div class="pure-u-1 pure-u-md-1-2">{col}</div>',
            ],
        ],
    ],
];
~~~

## Code

~~~php
<?php

declare(strict_types=1);

namespace herbie\plugins\grid;

use herbie\AbstractPlugin;
use herbie\Config;
use herbie\events\RenderSegmentEvent;
use herbie\Plugin;

#[Plugin('grid')]
final class GridPlugin extends AbstractPlugin
{
    private array $templates;

    public function __construct(Config $config)
    {
        $this->templates = $config->getAsArray('plugins.grid.templates');
    }

    public function eventListeners(): array
    {
        // Priority 100 runs before the Markdown plugin (priority 1).
        return [
            [RenderSegmentEvent::class, $this->onRenderSegment(...), 100],
        ];
    }

    public function onRenderSegment(RenderSegmentEvent $event): void
    {
        $segment = $event->getSegment();
        $replaced = preg_replace_callback(
            '/--\s+grid\s+(.+?)--(.*?)--\s+grid\s+--/msi',
            $this->replace(...),
            $segment,
        );
        $event->setSegment($replaced ?? $segment);
    }

    private function replace(array $matches): string
    {
        $template = $this->templates[trim($matches[1])] ?? null;
        if ($template === null) {
            return $matches[0]; // unknown template: leave the markup untouched
        }

        $cols = preg_split('/--\r?\n/', trim($matches[2])) ?: [];
        $html = '';
        foreach ($cols as $i => $col) {
            $colTemplate = $template['cols'][$i] ?? '{col}';
            $html .= strtr($colTemplate, [
                '{col}' => trim($col),
                '{index}' => (string)($i + 1),
            ]);
        }
        return strtr($template['row'] ?? '{row}', ['{row}' => $html]);
    }
}
~~~

## Usage

~~~php
'enabledPlugins' => 'twig,markdown,grid',
~~~

~~~markdown
-- grid two --
Left column
--
Right column
-- grid --
~~~

The placeholder `{index}` is available in a column template if you need the column number.
Because the listener runs before the Markdown parser, the generated HTML survives the parsing step with the default Markdown configuration (`html_input: allow`). With `html_input: escape` or `strip`, the generated tags are escaped or removed; see [Markdown](doc/contents/markdown).
