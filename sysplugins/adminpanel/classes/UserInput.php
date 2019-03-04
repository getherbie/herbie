<?php

namespace herbie\sysplugins\adminpanel\classes;

use Psr\Http\Message\ServerRequestInterface;

class UserInput
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getQueryParam($name, $filter = FILTER_DEFAULT, $options = null)
    {
        $params = $this->request->getQueryParams();
        $value = $params[$name] ?? '';
        $filtered = $this->filterVar($value, $filter, $options);
        return $filtered;
    }

    /**
     * @param $name
     * @param int $filter
     * @param null $options
     * @return mixed|string
     */
    public function getBodyParam($name, $filter = FILTER_DEFAULT, $options = null)
    {
        $data = $this->request->getParsedBody();
        $value = $data[$name] ?? '';
        $filtered = $this->filterVar($value, $filter, $options);
        return $filtered;
    }

    /**
     * @param $variable
     * @param int $filter
     * @param null $options
     * @return mixed
     */
    private function filterVar($variable, $filter = FILTER_DEFAULT, $options = null)
    {
        return filter_var($variable, $filter, $options);
    }
}
