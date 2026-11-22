<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\BoolConfig;
use Deptrac\Deptrac\Contract\Config\Collector\ClassNameRegexConfig;
use Deptrac\Deptrac\Contract\Config\Collector\DirectoryConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    // Public extension API, every plugin may depend on it.
    $extensionApiClasses = ClassNameRegexConfig::create(
        '#^herbie\\(AbstractPlugin|Plugin|PluginInterface|InstallablePlugin|Config|Alias|Assets|Translator|TwigRenderer|TwigStringLoader|UrlManager|EventManager|AbstractNode|Page|PageFactory|PageList|PagePersistenceInterface|PageRepositoryInterface|PageTrail|PageTree|Pagination|QueryBuilder|Site|DataRepositoryInterface|Cache|AbstractEvent)$#',
    );
    // The event classes are part of the extension API as well.
    $extensionApiEvents = DirectoryConfig::create('system/events/.*');

    $extensionApi = Layer::withName('ExtensionApi')->collectors(
        $extensionApiClasses,
        $extensionApiEvents,
    );
    $internals = Layer::withName('Internals')->collectors(
        BoolConfig::create(
            [DirectoryConfig::create('system/.*')],
            [$extensionApiClasses, $extensionApiEvents],
        ),
    );
    $plugins = Layer::withName('Plugins')->collectors(
        DirectoryConfig::create('plugins/.*'),
    );

    $config
        ->paths('system', 'plugins')
        ->layers($extensionApi, $internals, $plugins)
        ->rulesets(
            Ruleset::forLayer($extensionApi)->accesses($internals),
            Ruleset::forLayer($internals)->accesses($extensionApi),
            Ruleset::forLayer($plugins)->accesses($extensionApi),
        );
};
