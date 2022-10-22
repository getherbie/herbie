<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Server\MiddlewareInterface;

interface PluginInterface
{
    public function apiVersion(): int;

    /**
     * @return string[]
     */
    public function commands(): array;

    /**
     * @return array<int, array{0: string, 1: callable, 2?: int}>
     */
    public function events(): array;

    /**
     * @return array<int, array{string, callable}>
     */
    public function filters(): array;

    /**
     * @return array<int, MiddlewareInterface|callable|string>
     */
    public function appMiddlewares(): array;

    /**
     * @return array<int, array{string, MiddlewareInterface|callable|string}>
     */
    public function routeMiddlewares(): array;

    /**
     * @return array<int, TwigFilter|\Twig\TwigFilter|array{0: string, 1: callable, 2?: array<string, mixed>}>
     */
    public function twigFilters(): array;

    /**
     * @return array<string, mixed>
     */
    public function twigGlobals(): array;

    /**
     * @return array<int, TwigFunction|\Twig\TwigFunction|array{0: string, 1: callable, 2?: array<string, mixed>}>
     */
    public function twigFunctions(): array;

    /**
     * @return array<int, TwigTest|\Twig\TwigTest|array{0: string, 1: callable, 2?: array<string, mixed>}>
     */
    public function twigTests(): array;
}
