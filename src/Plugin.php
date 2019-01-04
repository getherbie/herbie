<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 30.12.18
 * Time: 10:21
 */

declare(strict_types=1);

namespace Herbie;

use herbie\plugin\shortcode\classes\Shortcode;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class Plugin implements ListenerAggregateInterface
{
    /**
     * @var Application
     */
    private $herbie;

    /**
     * @var array
     */
    private $listeners = [];

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

    /**
     * @return Alias
     */
    protected function getAlias()
    {
        return $this->herbie->getAlias();
    }

    /**
     * @return Config
     */
    protected function getConfig()
    {
        return $this->herbie->getConfig();
    }

    /**
     * @return \herbie\plugin\twig\classes\Twig
     */
    protected function getTwig()
    {
        return $this->herbie->getTwig();
    }

    /**
     * @return Menu\MenuList
     */
    protected function getMenuList()
    {
        return $this->herbie->getMenuList();
    }

    /**
     * @return Menu\MenuTree
     */
    protected function getMenuTree()
    {
        return $this->herbie->getMenuTree();
    }

    /**
     * @return Menu\RootPath
     */
    protected function getMenuRootPath()
    {
        return $this->herbie->getMenuRootPath();
    }

    /**
     * @return Page
     */
    protected function getPage()
    {
        return $this->herbie->getPage();
    }

    /**
     * @return Url\UrlGenerator
     */
    protected function getUrlGenerator()
    {
        return $this->herbie->getUrlGenerator();
    }

    /**
     * @return Repository\DataRepositoryInterface
     */
    protected function getDataRepository()
    {
        return $this->herbie->getDataRepository();
    }

    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function getRequest()
    {
        return $this->herbie->getRequest();
    }

    /**
     * @return \Ausi\SlugGenerator\SlugGeneratorInterface
     */
    protected function getSlugGenerator()
    {
        return $this->herbie->getSlugGenerator();
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        return $this->herbie->getTranslator();
    }

    /**
     * @return Assets
     */
    protected function getAssets()
    {
        return $this->herbie->getAssets();
    }

    /**
     * @return Environment
     */
    protected function getEnvironment()
    {
        return $this->herbie->getEnvironment();
    }

    /**
     * @param Shortcode $shortcode
     */
    protected function setShortcode(Shortcode $shortcode)
    {
        $this->herbie->setShortcode($shortcode);
    }

    /**
     * @param $twig
     */
    protected function setTwig($twig)
    {
        $this->herbie->setTwig($twig);
    }

    protected function getPageRepository()
    {
        return $this->herbie->getPageRepository();
    }

    protected function getHttpFactory()
    {
        return $this->herbie->getHttpFactory();
    }
}
