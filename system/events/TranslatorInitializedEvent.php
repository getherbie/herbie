<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;
use herbie\Translator;

final class TranslatorInitializedEvent extends AbstractEvent
{
    public function __construct(private Translator $translator)
    {
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }
}
