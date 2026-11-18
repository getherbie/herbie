<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;
use herbie\InstallablePlugin;

final class PluginsInitializedEvent extends AbstractEvent
{
    /**
     * @param InstallablePlugin[] $loadedPlugins
     * @param string[] $pluginPaths
     */
    public function __construct(private readonly array $loadedPlugins, private readonly array $pluginPaths) {}

    /**
     * @return InstallablePlugin[]
     */
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * @return string[]
     */
    public function getPluginPaths(): array
    {
        return $this->pluginPaths;
    }
}
