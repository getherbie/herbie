<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 30.12.18
 * Time: 10:21
 */

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

    public function __construct(Application $herbie)
    {
        $this->herbie = $herbie;
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        // overwrite in concrete plugin
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            $events->detach($listener);
            unset($this->listeners[$index]);
        }
    }

}
