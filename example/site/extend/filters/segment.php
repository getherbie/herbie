<?php

use herbie\FilterInterface;

return ['renderSegment', function (string $context, array $params, FilterInterface $filter): string {
    $context = $context
        . '<div style="display:none" class="example-site-extend-filters-segment">'
        . __FUNCTION__
        . '</div>';
    return $filter->next($context, $params, $filter);
}];
