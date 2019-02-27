<?php

namespace herbie\sysplugins\adminpanel\classes;

class Payload
{
    /** A command/query was accepted for later processing. */
    const ACCEPTED = 'ACCEPTED';

    /** User is authenticated. */
    const AUTHENTICATED = 'AUTHENTICATED';

    /** User is authorized. */
    const AUTHORIZED = 'AUTHORIZED';

    /** A creation command succeeded. */
    const CREATED = 'CREATED';

    /** A deletion command succeeded. */
    const DELETED = 'DELETED';

    /** There was a major error of some sort. */
    const ERROR = 'ERROR';

    /** There was a failure of some sort. */
    const FAILURE = 'FAILURE';

    /** A query successfully returned results. */
    const FOUND = 'FOUND';

    /** A command/query was not accepted for processing. */
    const NOT_ACCEPTED = 'NOT_ACCEPTED';

    /** User is not authenticated. */
    const NOT_AUTHENTICATED = 'NOT_AUTHENTICATED';

    /** User is not authorized. */
    const NOT_AUTHORIZED = 'NOT_AUTHORIZED';

    /** A creation command failed. */
    const NOT_CREATED = 'NOT_CREATED';

    /** A deletion command failed. */
    const NOT_DELETED = 'NOT_DELETED';

    /** A query failed to return results. */
    const NOT_FOUND = 'NOT_FOUND';

    /** An update command failed. */
    const NOT_UPDATED = 'NOT_UPDATED';

    /** User input was not valid. */
    const NOT_VALID = 'NOT_VALID';

    /** A command/query is in-process but not finished. */
    const PROCESSING = 'PROCESSING';

    /** There was a success of some sort (generic). */
    const SUCCESS = 'SUCCESS';

    /** An update command succeeded. */
    const UPDATED = 'UPDATED';

    /** User input was valid. */
    const VALID = 'VALID';

    /**
     *
     * The payload status.
     *
     * @var mixed
     *
     */
    protected $status;

    /**
     *
     * The domain input.
     *
     * @var mixed
     *
     */
    protected $input;

    /**
     *
     * The domain output.
     *
     * @var mixed
     *
     */
    protected $output;

    /**
     *
     * Messages reported by the domain.
     *
     * @var mixed
     *
     */
    protected $messages;

    /**
     *
     * Arbitrary extra information from the domain.
     *
     * @var mixed
     *
     */
    protected $extras;

    /**
     *
     * Sets the payload status.
     *
     * @param mixed $status The payload status.
     *
     * @return self
     *
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     *
     * Gets the payload status.
     *
     * @return mixed
     *
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     *
     * Sets the domain input.
     *
     * @param mixed $input The domain input.
     *
     * @return self
     *
     */
    public function setInput($input)
    {
        $this->input = $input;
        return $this;
    }

    /**
     *
     * Gets the domain input.
     *
     * @return mixed
     *
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     *
     * Sets the domain output.
     *
     * @param mixed $output The domain output.
     *
     * @return self
     *
     */
    public function setOutput($output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     *
     * Gets the domain output.
     *
     * @return mixed
     *
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     *
     * Sets the domain messages.
     *
     * @param mixed $messages The domain messages.
     *
     * @return self
     *
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     *
     * Gets the domain messages.
     *
     * @return mixed
     *
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     *
     * Sets arbitrary extra domain information.
     *
     * @param mixed $extras The domain extras.
     *
     * @return self
     *
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;
        return $this;
    }

    /**
     *
     * Gets the arbitrary extra domain information.
     *
     * @return mixed
     *
     */
    public function getExtras()
    {
        return $this->extras;
    }
}
