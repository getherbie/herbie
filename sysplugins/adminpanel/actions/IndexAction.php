<?php

namespace herbie\sysplugins\adminpanel\actions;

use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use herbie\TwigRenderer;

class IndexAction
{
    /**
     * @var TwigRenderer
     */
    private $twig;

    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * IndexAction constructor.
     * @param PayloadFactory $payloadFactory
     * @param TwigRenderer $twig
     */
    public function __construct(PayloadFactory $payloadFactory, TwigRenderer $twig)
    {
        $this->twig = $twig;
        $this->payloadFactory = $payloadFactory;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();

        try {
            $output = $this->twig->renderTemplate('@sysplugin/adminpanel/views/index.twig');
            return $payload
                ->setStatus(Payload::FOUND)
                ->setOutput($output);
        } catch (\Throwable $t) {
            return $payload
                ->setStatus(Payload::ERROR)
                ->setOutput($t);
        }
    }
}
