<?php

namespace herbie\sysplugins\adminpanel;

use AltoRouter;
use Ausi\SlugGenerator\SlugGenerator;
use Firebase\JWT\JWT;
use herbie\Alias;
use herbie\Configuration;
use herbie\Environment;
use herbie\Plugin;
use herbie\sysplugins\adminpanel\classes\MediaUserInput;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use herbie\sysplugins\adminpanel\classes\WebUser;
use herbie\SystemException;
use herbie\TwigRenderer;
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

    private $request;

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
        $this->container->set(PayloadFactory::class, function () {
            return new PayloadFactory();
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
        $this->request = $request;

        $requestedRoute = $request->getAttribute(HERBIE_REQUEST_ATTRIBUTE_ROUTE);
        $baseUrl = rtrim($this->environment->getBaseUrl(), '/') . '/';

        $route = $this->config->plugins->adminpanel->route ?? 'adminpanel';

        if (strpos($requestedRoute, $route) === 0) {
            $webUser = $this->getUserFromToken();
            $this->container->set(WebUser::class, $webUser);

            $router = new AltoRouter();
            $router->setBasePath($baseUrl);

            $router->map('GET', $route, actions\IndexAction::class, 'index');

            // auth
            $router->map('POST', $route . '/auth', actions\AuthAction::class, 'auth');

            // data
            $router->map('GET', $route . '/data', actions\data\IndexAction::class, 'data/index');

            // media
            $router->map('GET', $route . '/media', actions\media\IndexAction::class, 'media/index');
            $router->map('POST', $route . '/media/addfolder', actions\media\AddFolderAction::class, 'media/addfolder');
            $router->map('DELETE', $route . '/media/deletefile', actions\media\DeleteFileAction::class, 'media/deletefile');
            $router->map('DELETE', $route . '/media/deletefolder', actions\media\DeleteFolderAction::class, 'media/deletefolder');
            $router->map('POST', $route . '/media/uploadfile', actions\media\UploadFileAction::class, 'media/uploadfile');

            // page
            $router->map('GET', $route . '/page', actions\page\IndexAction::class, 'page/index');

            // test
            $router->map('GET', $route . '/test', actions\test\IndexAction::class, 'test/index');
            $router->map('DELETE', $route . '/test/[i:id]', actions\test\DeleteAction::class, 'test/delete');
            $router->map('POST', $route . '/test/add', actions\test\AddAction::class, 'test/add');

            // tools
            $router->map('GET', $route . '/tools', actions\tools\IndexAction::class, 'tools/index');

            $response = HttpFactory::instance()->createResponse(200);

            try {
                $match = $router->match();

                if ($match) {
                    if (!in_array($match['name'], ['index', 'auth']) && !$webUser->isAuthenticated) {
                        return $response->withStatus(401);
                    }

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

                    $payload = call_user_func_array($action, $match['params']);

                    // test for return value of invoked action
                    if (!$payload instanceof Payload) {
                        throw SystemException::serverError('Payload object expected');
                    }

                    // map payload to http status
                    $response = $this->mapStatusCodes($response, $payload);

                    // handle body
                    $output = $payload->getOutput();
                    if (is_array($output)) {
                        header('Content-Type: application/json');
                        $response->getBody()->write(json_encode($output));
                    } elseif ($output !== null) {
                        $response->getBody()->write($output);
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

    private function getUserFromToken(): ?WebUser
    {
        $user = new Webuser();
        $token = $this->getBearerToken();

        if (empty($token)) {
            return $user;
        }

        try {
            $decoded = JWT::decode($token, 'my_secret_key', ['HS256']);
            if (!empty($decoded->user)) {
                $user->username = $decoded->user;
                $user->isAuthenticated = true;
            }
            return $user;
        } catch (\Exception $e) {
            return $user;
        }
    }

    private function getBearerToken(): ?string
    {
        $bearerToken = $this->request->getHeaderLine('Authorization');
        if (strpos($bearerToken, 'Bearer ') === 0) {
            return substr($bearerToken, 7);
        }
        return null;
    }

    private function mapStatusCodes(ResponseInterface $response, Payload $payload)
    {
        switch ($payload->getStatus()) {
            case Payload::ACCEPTED:
                $response = $response->withStatus(202);
                break;
            case Payload::AUTHENTICATED:
                $response = $response->withStatus(200);
                break;
            case Payload::AUTHORIZED:
                $response = $response->withStatus(200);
                break;
            case Payload::CREATED:
                $response = $response->withStatus(200);
                break;
            case Payload::DELETED:
                $response = $response->withStatus(204);
                break;
            case Payload::ERROR:
                $response = $response->withStatus(500);
                break;
            case Payload::FAILURE:
                $response = $response->withStatus(500);
                break;
            case Payload::FOUND:
                $response = $response->withStatus(200);
                break;
            case Payload::NOT_ACCEPTED:
                $response = $response->withStatus(400);
                break;
            case Payload::NOT_AUTHENTICATED:
                $response = $response->withStatus(401);
                break;
            case Payload::NOT_AUTHORIZED:
                $response = $response->withStatus(400);
                break;
            case Payload::NOT_CREATED:
                $response = $response->withStatus(400);
                break;
            case Payload::NOT_DELETED:
                $response = $response->withStatus(400);
                break;
            case Payload::NOT_FOUND:
                $response = $response->withStatus(404);
                break;
            case Payload::NOT_UPDATED:
                $response = $response->withStatus(400);
                break;
            case Payload::NOT_VALID:
                $response = $response->withStatus(422);
                break;
            case Payload::PROCESSING:
                $response = $response->withStatus(200);
                break;
            case Payload::SUCCESS:
                $response = $response->withStatus(200);
                break;
            case Payload::UPDATED:
                $response = $response->withStatus(200);
                break;
            case Payload::VALID:
                $response = $response->withStatus(200);
                break;
            default:
                $response = $response->withStatus(500);
        }
        return $response;
    }
}
