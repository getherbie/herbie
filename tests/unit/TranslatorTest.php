<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Translator;

final class TranslatorTest extends Unit
{
    public function testTranslateWithoutMessagesReturnsTheMessage(): void
    {
        $translator = new Translator('de');
        $translator->init();

        $this->assertSame('Hello', $translator->translate('test', 'Hello'));
    }

    public function testTranslateIsAnAliasOfT(): void
    {
        $translator = new Translator('de');

        $this->assertSame('Hello', $translator->t('test', 'Hello'));
    }

    public function testTranslationFromMessagesFile(): void
    {
        $translator = new Translator('de');
        $translator->addPath('fixture', $this->getFixturePath());
        $translator->init();

        $this->assertSame('Hallo Welt', $translator->t('fixture', 'Hello World'));
    }

    public function testUnknownMessageIsReturnedUnchanged(): void
    {
        $translator = new Translator('de');
        $translator->addPath('fixture', $this->getFixturePath());
        $translator->init();

        $this->assertSame('Not in the messages file', $translator->t('fixture', 'Not in the messages file'));
    }

    public function testUnknownCategoryFallsBackToTheMessage(): void
    {
        $translator = new Translator('de');
        $translator->addPath('fixture', $this->getFixturePath());
        $translator->init();

        $this->assertSame('Hello World', $translator->t('not-existing', 'Hello World'));
    }

    public function testPlaceholdersAreReplaced(): void
    {
        $translator = new Translator('de');
        $translator->addPath('fixture', $this->getFixturePath());
        $translator->init();

        $this->assertSame(
            'Guten Tag, Ada!',
            $translator->t('fixture', 'Greeting', ['name' => 'Ada'])
        );
    }

    public function testPlaceholdersAreReplacedInUnknownMessages(): void
    {
        $translator = new Translator('de');

        $this->assertSame(
            'Hello Ada',
            $translator->t('test', 'Hello {name}', ['name' => 'Ada'])
        );
    }

    public function testUnknownPlaceholderIsKept(): void
    {
        $translator = new Translator('de');

        $this->assertSame(
            'Hello {name}',
            $translator->t('test', 'Hello {name}', ['other' => 'value'])
        );
    }

    public function testInitReturnsTheTranslator(): void
    {
        $translator = new Translator('de');

        $this->assertSame($translator, $translator->init());
    }

    public function testMissingLanguageFileIsIgnored(): void
    {
        $translator = new Translator('not-existing-language');
        $translator->addPath('fixture', $this->getFixturePath());

        $this->assertSame('Hello World', $translator->init()->t('fixture', 'Hello World'));
    }

    public function testAddPathAcceptsArray(): void
    {
        $translator = new Translator('de');
        $translator->addPath('fixture', [$this->getFixturePath()]);
        $translator->init();

        $this->assertSame('Hallo Welt', $translator->t('fixture', 'Hello World'));
    }

    public function testGetMessages(): void
    {
        $translator = new Translator('de');
        $translator->addPath('fixture', $this->getFixturePath());
        $translator->init();

        $messages = $translator->getMessages();

        $this->assertArrayHasKey('de', $messages);
        $this->assertArrayHasKey('fixture', $messages['de']);
        $this->assertSame('Hallo Welt', $messages['de']['fixture']['Hello World']);
    }

    private function getFixturePath(): string
    {
        return dirname(__DIR__) . '/_data/messages';
    }
}
