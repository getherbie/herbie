<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-02-10
 * Time: 10:54
 */

namespace herbie\sysplugins\adminpanel\actions\test;

use Psr\Http\Message\ServerRequestInterface;

class AddAction
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return array
     * @see https://stackoverflow.com/questions/3290182/rest-http-status-codes-for-failed-validation-or-invalid-duplicate
     */
    public function __invoke()
    {
        $input = json_decode($this->request->getBody(), true);
        $name = $input['name'] ?? '';
        $name = trim($name);

        $errors = [];

        if (empty($name)) {
            $errors['name'][] = 'Name is required';
        }

        if (!empty($errors)) {
            header($_SERVER["SERVER_PROTOCOL"] . ' 400 Bad Request');
            return ['errors' => $errors];
        }

        return [
            'name' => $name
        ];
    }

}
