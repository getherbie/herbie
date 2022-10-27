<?php

declare(strict_types=1);

namespace tests\_data\site\extend\filters;

use herbie\FilterInterface;

return ['renderLayout', function (string $context, array $params, FilterInterface $filter): string {
    $context = str_replace(
        '</body>',
        '<div style="display:none" class="example-site-extend-filters-layout">' . __FUNCTION__ . '</div></body>',
        $context
    );
    return $filter->next($context, $params, $filter);
}];
