<?php

namespace herbie\sysplugins\adminpanel;

use AltoRouter;
use Ausi\SlugGenerator\SlugGenerator;
use Herbie\Alias;
use Herbie\Configuration;
use Herbie\Environment;
use Herbie\Plugin;
use herbie\sysplugins\adminpanel\classes\MediaUserInput;
use Herbie\TwigRenderer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tebe\HttpFactory\HttpFactory;

class AdminpanelPlugin extends Plugin
{
    /**
     * @var Environment
     */
    private $environment;
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var Configuration
     */
    private $config;
    /**
     * @var TwigRenderer
     */
    private $twig;

    /**
     * AdminpanelPlugin constructor.
     * @param Environment $environment
     * @param ContainerInterface $container
     * @param Configuration $config
     * @param TwigRenderer $twig
     */
    public function __construct(Environment $environment, ContainerInterface $container, Configuration $config, TwigRenderer $twig)
    {
        $this->environment = $environment;
        $this->container = $container;
        $this->config = $config;
        $this->twig = $twig;
        $this->container->set(MediaUserInput::class, function (ContainerInterface $c) {
            return new MediaUserInput(
                $c->get(Alias::class),
                $c->get(ServerRequestInterface::class),
                $c->get(SlugGenerator::class)
            );
        });
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return [
            [$this, 'adminpanelModule']
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     * @return ResponseInterface
     * @throws \Exception
     */
    public function adminpanelModule(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $requestedRoute = $request->getAttribute(HERBIE_REQUEST_ATTRIBUTE_ROUTE);
        $baseUrl = rtrim($this->environment->getBaseUrl(), '/') . '/';

        $route = $this->config->plugins->adminpanel->route ?? 'adminpanel';

        if (strpos($requestedRoute, $route) === 0) {
            $router = new AltoRouter();
            $router->setBasePath($baseUrl);

            $router->map('GET', $route, actions\IndexAction::class, 'index');

            // data
            $router->map('GET', $route.'/data', actions\data\IndexAction::class, 'data/index');

            // media
            $router->map('GET', $route.'/media', actions\media\IndexAction::class, 'media/index');
            $router->map('POST', $route.'/media/addfolder', actions\media\AddFolderAction::class, 'media/addfolder');
            $router->map('DELETE', $route.'/media/deletefile', actions\media\DeleteFileAction::class, 'media/deletefile');
            $router->map('DELETE', $route.'/media/deletefolder', actions\media\DeleteFolderAction::class, 'media/deletefolder');
            $router->map('POST', $route.'/media/uploadfile', actions\media\UploadFileAction::class, 'media/uploadfile');

            // page
            $router->map('GET', $route.'/page', actions\page\IndexAction::class, 'page/index');

            // test
            $router->map('GET', $route.'/test', actions\test\IndexAction::class, 'test/index');
            $router->map('DELETE', $route.'/test/[i:id]', actions\test\DeleteAction::class, 'test/delete');
            $router->map('POST', $route.'/test/add', actions\test\AddAction::class, 'test/add');

            // tools
            $router->map('GET', $route.'/tools', actions\tools\IndexAction::class, 'tools/index');

            $response = HttpFactory::instance()->createResponse(200);

            try {
                $match = $router->match();

                if ($match) {
                    $class = new \ReflectionClass($match['target']);
                    $constructor = $class->getConstructor();
                    $constructorParams = [];
                    if ($constructor) {
                        foreach ($constructor->getParameters() as $param) {
                            $classNameToInject = $param->getClass()->getName();
                            $constructorParams[] = $this->container->get($classNameToInject);
                        };
                    }
                    $action = new $match['target'](...$constructorParams);

                    $body = call_user_func_array($action, $match['params']);

                    if (is_array($body)) {
                        header('Content-Type: application/json');
                        $response->getBody()->write(json_encode($body));
                    } else {
                        $response->getBody()->write($body);
                    }

                } else {
                    // no route was matched
                    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
                    $response->getBody()->write('Resource not found');
                }

            } catch (\Throwable $t) {
                header($_SERVER["SERVER_PROTOCOL"] . ' 500');
                $error = [
                    'code' => $t->getCode(),
                    'message' => $t->getMessage(),
                ];
                $response->getBody()->write(json_encode($error));
            }

            return $response;
        }

        $response = $next->handle($request);

        // prepend adminpanel to html body
        $panel = $this->twig->renderTemplate('@sysplugin/adminpanel/views/panel.twig', [
            'controller' => 'xxx'
        ]);
        $regex = '/<body(.*)>/';
        $replace = '<body$1>' . $panel;

        $response->getBody()->rewind();
        $content = preg_replace($regex, $replace, $response->getBody()->getContents());
        $response->getBody()->rewind();
        $response->getBody()->write($content);

        return $response;
    }
}
