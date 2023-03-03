<?php

namespace herbie\sysplugins\adminpanel\validators;

use herbie\Alias;
use Rakit\Validation\Rule;

class FileNotExistsRule extends Rule
{
    protected Alias $alias;
    protected $message = 'The file ":value" already exists';

    /** @var array */
    protected $fillableParams = ['alias'];

    public function __construct(Alias $alias)
    {
        $this->alias = $alias;
    }

    public function check($value): bool
    {
        $this->requireParameters($this->fillableParams);
        
        $this->attribute->setRequired(true);

        $aliasedPathWithPlaceholder = $this->parameter('alias');
        $pathWithPlaceholder = $this->alias->get($aliasedPathWithPlaceholder);
        $path = str_replace('{value}', $value, $pathWithPlaceholder);

        return !file_exists($path);
    }
}
