<?php

namespace herbie\plugin\adminpanel\controllers;

class DefaultController extends Controller
{
    public function errorAction($request)
    {
        if ($this->request->isXmlHttpRequest()) {
            $this->sendErrorHeader($this->t('Invalid action parameter.'));
        }
        return $this->render('default/error.twig', []);
    }

    public function loginAction($request)
    {
        if ($this->request->getMethod() == 'POST') {
            $password = $request->getPost('password', null);
            if (md5($password) == $this->config->get('plugins.config.adminpanel.password')) {
                $_SESSION['LOGGED_IN'] = true;
                $this->twig->getEnvironment()
                    ->getExtension('herbie\\plugin\\twig\\classes\\HerbieExtension')
                    ->functionRedirect('adminpanel');
            }
        }
        return $this->render('default/login.twig', []);
    }

    public function logoutAction()
    {
        $_SESSION['LOGGED_IN'] = false;
        $this->twig->getEnvironment()
            ->getExtension('herbie\\plugin\\twig\\classes\\HerbieExtension')
            ->functionRedirect('');
    }
}
