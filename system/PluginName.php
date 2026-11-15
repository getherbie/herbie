<?php

declare(strict_types=1);

namespace herbie;

use Attribute;

/**
 * Marks a class as a plugin and gives the plugin its name.
 * The name is used in the config option "enabledPlugins" and as key "plugins.<name>" in the config.
 *
 * @see PluginDiscovery
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class PluginName
{
    public function __construct(public string $name) {}
}
