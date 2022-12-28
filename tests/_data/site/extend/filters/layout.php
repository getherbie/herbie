<?php

declare(strict_types=1);

namespace herbie\tests\_data\site\extend\filters;

use herbie\events\RenderLayoutEvent;

return [
    RenderLayoutEvent::class,
    function (RenderLayoutEvent $event): void {
        $content = str_replace(
            '</body>',
            '<div style="display:none" class="example-site-extend-filters-layout">'
            . __FUNCTION__
            . '</div></body>',
            $event->getContent()
        );
        $event->setContent($content);
    }
];
