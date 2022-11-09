<?php

use herbie\Application;
use herbie\ApplicationPaths;

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
class UnitTester extends \Codeception\Actor
{
    use _generated\UnitTesterActions;

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

    public function initTwigRenderer(string $appDir, string $siteDir, ?string $vendorDir = null): \herbie\TwigRenderer
    {
        return $this->initApplication($appDir, $siteDir, $vendorDir)->getTwigRenderer();
    }
}
