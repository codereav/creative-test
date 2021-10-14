<?php declare(strict_types=1);

namespace App\Provider;

use App\Container\Container;
use App\Support\Config;
use App\Support\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Symfony\Component\Yaml\Yaml;

class WebProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $this->defineRoutes($container);
    }

    protected function defineRoutes(Container $container): void
    {
        $router = $container->get(RouteCollectorInterface::class);

        $router->group('/', function (RouteCollectorProxyInterface $router) use ($container) {
            $routes = self::getRoutes($container);
            foreach ($routes as $routeName => $routeConfig) {
                self::defineControllerDi($container, $routeConfig);
                $router->{$routeConfig['method']}($routeConfig['path'] ?? '', $routeConfig['controller'] . ':' . $routeConfig['action'])
                    ->setName($routeName);
            }
        });
    }

    protected static function defineControllerDi(Container $container, array $routeConfig): void
    {
        if (!$container->has($routeConfig['controller'])) {
            $container->set($routeConfig['controller'], static function (ContainerInterface $container) use ($routeConfig) {
                return new $routeConfig['controller'](...
                    array_map(function ($arg) use ($container) {
                        return $container->has($arg) ? $container->get($arg) : $arg;
                    },
                        $routeConfig['construct'] ?? []
                    )
                );
            });
        }

    }

    protected static function getRoutes(Container $container): array
    {
        return Yaml::parseFile($container->get(Config::class)->get('base_dir') . '/config/routes.yaml');
    }
}
