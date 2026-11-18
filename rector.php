<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__,
    ])
    ->withSkip([
        '*/vendor/*',
        '*/runtime/*',

        // Adds `readonly` to anonymous classes (`new readonly class`). Codeception's test
        // loader (Parser::extractClass) only recognizes `new class`, so it then misreads
        // `implements` as the class name and fails with "Class ...\implements does not exist".
        \Rector\Php82\Rector\Class_\ReadOnlyClassRector::class,

        // Marks properties as `readonly`. Tests set private properties via reflection, which
        // then fails with "Cannot modify readonly property".
        \Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class,

        // Promotes constructor assignments to promoted properties. Tests create objects via
        // newInstanceWithoutConstructor(), so promoted properties have no default value and
        // fail with "must not be accessed before initialization".
        \Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class,
    ])
    ->withPhpSets(php83: true);
