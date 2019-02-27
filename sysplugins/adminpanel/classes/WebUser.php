<?php

namespace herbie\sysplugins\adminpanel\classes;

class WebUser
{
    public $username;
    public $isAuthenticated;

    public function __construct($isAuthenticated = false, $username = null)
    {
        $this->isAuthenticated = $isAuthenticated;
        $this->username = $username;
    }
}
