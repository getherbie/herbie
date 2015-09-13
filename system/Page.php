<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
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
    private $pageLoader;

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
     * @return \stdClass
     */
    public function getSegment($id)
    {
        $segment = new \stdClass();
        $segment->string = '';
        if (array_key_exists($id, $this->segments)) {
            $segment->string = $this->segments[$id];
        }
        return $segment;
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
    public function getData()
    {
        return $this->data;
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

    /**
     * Get the http status code depending on a set error code.
     * @return int
     */
    public function getStatusCode()
    {
        if (empty($this->data['error'])) {
            return 200;
        }
        if (empty($this->data['error']['code'])) {
            return 500;
        }
        return $this->data['error']['code'];
    }

    /**
     * @param \Exception $e
     */
    public function setError(\Exception $e)
    {
        $this->data['error'] = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }

    /**
     * @param string $alias
     * @return static
     */
    public static function create($alias)
    {
        $loader = DI::get('Loader\PageLoader');
        $page = new static();
        $page->setLoader($loader);
        $page->load($alias);
        return $page;
    }

    /**
     * @return string
     */
    public function getDefaultBlocksPath()
    {
        $pathinfo = pathinfo($this->path);
        return $pathinfo['dirname'] . '/_' . $pathinfo['filename'];
    }

}
