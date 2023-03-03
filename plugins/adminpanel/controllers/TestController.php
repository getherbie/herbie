<?php

namespace herbie\sysplugins\adminpanel\controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TestController extends Controller
{
    public function formAction(ServerRequestInterface $request): ResponseInterface
    {
        $errors = [];
        
        if ($request->getMethod() === 'POST') {
            $errors = [
                'first_name' => 'This is wrong',
                'last_name' => 'This is wrong',
                'email' => 'This is wrong',
                'phone' => 'This is wrong',
            ];
        }
        $response = $this->render('test/form.twig', [
            'errors' => $errors,
        ]);
        
        $status = empty($errors) ? 200 : 400;

        return $response->withStatus($status);
    }
}
