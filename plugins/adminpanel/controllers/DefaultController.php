<?php

namespace herbie\sysplugins\adminpanel\controllers;

use Psr\Http\Message\ServerRequestInterface;

class DefaultController extends Controller
{
    public function indexAction(ServerRequestInterface $request)
    {
        return $this->render('default/index.twig');
    }

    public function errorAction(ServerRequestInterface $request, \Exception $exception)
    {
        return $this->render('default/error.twig', [
            'exception' => $exception
        ]);
    }

    public function loginAction(ServerRequestInterface $request)
    {
        if ($this->request->getMethod() == 'POST') {
            $password = $request->getParsedBody()['password'] ?? '';
            if ($password === 'password') {
                $_SESSION['LOGGED_IN'] = true;
                return $this->redirect('default/index');
            }
        }
        return $this->render('default/login.twig', []);
    }

    public function logoutAction(ServerRequestInterface $request)
    {
        $_SESSION['LOGGED_IN'] = false;
        return $this->redirect('default/login');
    }
}
