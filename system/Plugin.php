<?php

declare(strict_types=1);

namespace herbie;

use Attribute;

/**
 * Marks a class as a plugin and describes it, e.g.
 * #[Plugin('markdown', requires: ['league/commonmark' => '^2.10'])].
 *
 * The name is used in the config option "enabledPlugins" and as key "plugins.<name>" in the config.
 * It must be the first argument.
 *
 * The packages in "requires" are Composer packages with a version constraint. A package without a
 * constraint is written as value, e.g. ['vendor/package']. The packages are checked when the plugin
 * is enabled. If one is missing or does not match the constraint, Herbie throws an exception that
 * says what to install.
 *
 * @see PluginDiscovery
 * @see PluginManager
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Plugin
{
    /**
     * @param array<int|string, string> $requires
     */
    public function __construct(public string $name, public array $requires = []) {}
}
