<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Http;

class Response
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var null|string
     */
    protected $body = null;

    /**
     * @var int|null
     */
    protected $status = null;

    /**
     * @param string $content
     * @param int $status
     * @param array $headers
     */
    public function __construct($content = '', $status = 200, array $headers = [])
    {
        $this->headers = $headers;
        $this->body = $content;
        $this->setStatus($status);
    }

    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function setStatus($status)
    {
        $this->status = intval($status);
    }

    public function send()
    {
        if ($this->status !== null) {
            header("HTTP/1.0 {$this->status}");
        }
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        print $this->body;
        $this->headers = [];
        $this->body = null;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Is response successful?
     *
     * @return bool
     *
     * @api
     */
    public function isSuccessful()
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function isRedirection()
    {
        return $this->status >= 300 && $this->status < 400;
    }

}
