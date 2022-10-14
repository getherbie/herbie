<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    /**
     * Define custom actions here
     */
    public function seeResponseContains(string $text)
    {
        $this->assertStringContainsString(
            $text,
            $this->getModule('PhpBrowser')->_getResponseContent(),
            'response contains'
        );
    }
}
