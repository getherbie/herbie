<?php

declare(strict_types=1);

namespace herbie;

use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

final class CacheInvalidArgumentException extends \InvalidArgumentException implements PsrInvalidArgumentException {}
