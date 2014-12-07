<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\simplecontact;

use Herbie;
use Herbie\Loader\FrontMatterLoader;
use Herbie\Menu;
use Twig_SimpleFunction;

class SimplecontactPlugin extends Herbie\Plugin
{
    protected $page;

    protected $errors = [];

    /**
     * @param Herbie\Event $event
     */
    public function onTwigInitialized(Herbie\Event $event)
    {
        $event['twig']->addFunction(
            new Twig_SimpleFunction('simplecontact', [$this, 'simplecontact'], ['is_safe' => ['html']])
        );
    }

    /**
     * @return string
     */
    public function simplecontact()
    {
        $sent = false;
        if ( $_SERVER['REQUEST_METHOD'] == "POST") {
            if ($this->validateFormData() ) {

                #$this->redirect('success');

                if ($this->sendEmail() ) {
                    $sent = true;
                }
            }
        }

        $config =  $this->getFormConfig();

        switch($this->app['action']) {
            case 'error':
                $content = $config['messages']['error'];
                break;
            case 'success':
                $content = $config['messages']['success'];
                break;
            default:
                $content = $this->app['twig']->render('@plugin/simplecontact/templates/form.twig', [
                    'simplecontact' => $config,
                    'page' => $this->app['page']->toArray(),
                    'errors' => $this->errors,
                    'vars' => $this->filterFormData(),
                    'route' => $this->app['route'],
                    'sent' => $sent
                ]);
        }

        return $content;

    }

    /**
     * @return bool
     */
    protected function validateFormData()
    {
        $config = $this->getFormConfig();
        $form_data = $this->filterFormData();
        extract($form_data); // name, email, message, antispam

        if(empty($name)) {
            $this->errors['name'] = $config['errors']['emptyField'];
        }
        if(empty($email)) {
            $this->errors['email'] = $config['errors']['emptyField'];
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = $config['errors']['invalidEmail'];
        }
        if(empty($message)) {
            $this->errors['message'] = $config['errors']['emptyField'];
        }

        return empty($this->errors);
    }

    /**
     * @return array
     */
    protected function filterFormData()
    {
        $defaults = [
            'name' => '',
            'email' => '',
            'message' => '',
            'antispam' => ''
        ];
        $definition = array(
            'name' => FILTER_SANITIZE_STRING,
            'email' => FILTER_SANITIZE_EMAIL,
            'message' => FILTER_SANITIZE_STRING,
            'antispam' => FILTER_SANITIZE_STRING
        );
        $filtered = (array)filter_input_array(INPUT_POST, $definition);
        return array_merge($defaults, $filtered);
    }

    /**
     * @return bool
     */
    protected function sendEmail()
    {
        $form = $this->filterFormData();

        // Antispam
        if('' == $form['antispam']) {
            return true;
        }

        $config = $this->getFormConfig();

        $recipient  = $config['recipient'];
        $subject    = $config['subject'];

        $message = "{$form['message']}\n\n";
        $message .= "Name: {$form['name']}\n";
        $message .= "Email: {$form['email']}\n\n";

        $headers = "From: {$form['name']} <{$form['email']}>";

        return mail($recipient, $subject, $message, $headers);
    }

    /**
     * @return array
     */
    protected function getFormConfig()
    {
        $config = (array) $this->config('plugins.simplecontact');
        if(isset($this->app['page']->simplecontact) && is_array($this->app['page']->simplecontact)) {
            $config = (array)$this->app['page']->simplecontact;
        }
        return $config;
    }

    /**
     * @param string $action
     */
    protected function redirect($action)
    {
        $route = $this->app['route'] . ':' . $action;
        $twigExt = $this->app['twig']->environment->getExtension('herbie');
        $twigExt->functionRedirect($route);
    }

}
