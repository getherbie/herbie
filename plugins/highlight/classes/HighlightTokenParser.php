<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/HighlightNode.php';

class HighlightTokenParser extends Twig_TokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();

        $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideSpacelessEnd'], true);
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new HighlightNode($name, $body, $lineno, $this->getTag());
    }

    /**
     * @param Twig_Token $token
     * @return bool
     */
    public function decideSpacelessEnd(Twig_Token $token)
    {
        return $token->test('endcode');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'code';
    }
}
