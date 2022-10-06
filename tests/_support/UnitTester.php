<?php

use herbie\Application;

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

    public function initApplication(string $siteDir, string $vendorDir): Application
    {
        $app = new Application($siteDir, $vendorDir);
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $app->getTranslator()->init();
        return $app;
    }

    public function initTwigRenderer(string $siteDir, string $vendorDir): \herbie\TwigRenderer
    {
        return $this->initApplication($siteDir, $vendorDir)->getTwigRenderer();
    }
}
