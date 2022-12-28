<?php

use Codeception\Actor;
use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class UnitTester extends Actor
{
    use _generated\UnitTesterActions;

    public function initTwigRenderer(string $appDir, string $siteDir, ?string $vendorDir = null): TwigRenderer
    {
        return $this->initApplication($appDir, $siteDir, $vendorDir)->getTwigRenderer();
    }

    /**
     * Define custom actions here
     */

    public function initApplication(string $appDir, string $siteDir, ?string $vendorDir = null): Application
    {
        $webDir = dirname(__DIR__) . '/_data/web';
        $app = new Application(new ApplicationPaths($appDir, $siteDir, $vendorDir, $webDir));
        $app->setScriptFile('index.php');
        $app->setScriptUrl('');
        $app->setBaseUrl('');
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $app->getTranslator()->init();
        return $app;
    }
}
