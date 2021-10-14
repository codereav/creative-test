<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouteCollectorInterface;
use Twig\Environment;

class HomeController
{
    public function __construct(
        private RouteCollectorInterface $routeCollector,
        private Environment             $twig,
        private EntityManagerInterface  $em
    )
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $this->twig->render(
                'home/index.html.twig',
                array_merge(['trailers' => $this->fetchData()], $this->getDebugInfo())
            );
        } catch (\Exception $e) {
            throw new HttpBadRequestException($request, $e->getMessage(), $e);
        }

        $response->getBody()->write($data);

        return $response;
    }

    public function info(ServerRequestInterface $request, ResponseInterface $response, array $uriData): ResponseInterface
    {
        $id = (int) $uriData['id'] ?? null;
        $trailer = $this->em->getRepository(Movie::class)->find($id);
        if (!$trailer instanceof Movie) {
            throw new HttpNotFoundException($request, 'Trailer is missing');
        }
        try {
            $data = $this->twig->render('home/info.html.twig', [
                'trailer' => $trailer,
            ]);
        } catch (\Exception $e) {
            throw new HttpBadRequestException($request, $e->getMessage(), $e);
        }

        $response->getBody()->write($data);

        return $response;
    }

    protected function fetchData(): Collection
    {
        $data = $this->em->getRepository(Movie::class)
            ->findBy([], ['pubDate' => 'desc']);

        return new ArrayCollection($data);
    }

    private function getDebugInfo(): array
    {
        return [
            'debug' => [
                'currentDateTime' => (new \DateTime())->format('Y-m-d H:i:s'),
                'currentController' => get_class($this),
                'currentMethod' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'],
            ],
        ];
    }
}
