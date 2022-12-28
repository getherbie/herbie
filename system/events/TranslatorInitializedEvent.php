<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;
use herbie\Translator;

final class TranslatorInitializedEvent extends AbstractEvent
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }
}
