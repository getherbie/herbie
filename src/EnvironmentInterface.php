<?php

namespace herbie;

interface EnvironmentInterface
{
    public function getBaseUrl(): string;
    public function getPathInfo(): string;
    public function getRoute(): string;
    public function getScriptFile(): string;
    public function getScriptUrl(): string;
}
