<?php

declare(strict_types=1);

namespace herbie\event;

use herbie\AbstractEvent;
use herbie\InstallablePlugin;

final class PluginsAttachedEvent extends AbstractEvent
{
    /** @var array<string, InstallablePlugin> */
    private array $loadedPlugins;

    /** @var array<string, string> */
    private array $pluginPaths;

    /**
     * @param InstallablePlugin[] $loadedPlugins
     * @param string[] $pluginPaths
     */
    public function __construct(array $loadedPlugins, array $pluginPaths)
    {
        $this->loadedPlugins = $loadedPlugins;
        $this->pluginPaths = $pluginPaths;
    }

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
