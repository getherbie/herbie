<?php

namespace herbie\sysplugins\adminpanel\classes;

/**
 *
 * A factory to create and return payload objects.
 *
 */
class PayloadFactory
{
    /**
     *
     * Returns a new Payload object.
     *
     * @return Payload
     *
     */
    public function newInstance()
    {
        return new Payload();
    }
}
