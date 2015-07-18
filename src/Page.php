<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <http://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use Herbie\Menu\ItemTrait;

/**
 * Stores the page.
 */
class Page
{

    use ItemTrait;

    /**
     * @var array
     */
    protected $segments = [];

    /**
     * @var PageLoader
     */
    protected $pageLoader;

    /**
     * @return string
     */
    public function getPath()
    {
        return isset($this->data['path']) ? $this->data['path'] : '';
    }

    /**
     * @return array
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     *
     * @param string $id
     * @return null|string
     */
    public function getSegment($id)
    {
        if (array_key_exists($id, $this->segments)) {
            return $this->segments[$id];
        }
        return null;
    }

    /**
     * @param array $data
     * @throws \LogicException
     */
    public function setData(array $data)
    {
        if (array_key_exists('segments', $data)) {
            throw new \LogicException("Field segments is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        switch ($format) {
            case 'md':
            case 'markdown':
                $format = 'markdown';
                break;
            case 'textile':
                $format = 'textile';
                break;
            default:
                $format = 'raw';
        }
        $this->data['format'] = $format;
    }

    /**
     * @param array $segments
     */
    public function setSegments(array $segments = [])
    {
        $this->segments = $segments;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'data' => $this->data,
            'segments' => $this->segments
        ];
    }

    /**
     * @param PageLoader $loader
     */
    public function setLoader(Loader\PageLoader $loader)
    {
        $this->pageLoader = $loader;
    }

    /**
     * @param $alias
     */
    public function load($alias)
    {
        $data = $this->pageLoader->load($alias);
        $this->setData($data['data']);
        $this->setSegments($data['segments']);
    }
}
