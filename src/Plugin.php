<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 30.12.18
 * Time: 10:21
 */

declare(strict_types=1);

namespace Herbie;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class Plugin implements ListenerAggregateInterface
{
    /**
     * @var Application
     */
    protected $herbie;

    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * Plugin constructor.
     * @param Application $herbie
     */
    public function __construct(Application $herbie)
    {
        $this->herbie = $herbie;
    }

    /**
     * @param EventManagerInterface $events
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        // overwrite in concrete plugin
    }

    /**
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events): void
    {
        foreach ($this->listeners as $index => $listener) {
            $events->detach($listener);
            unset($this->listeners[$index]);
        }
    }
}
