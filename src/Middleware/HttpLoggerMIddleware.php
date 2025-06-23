<?php

namespace Horus\Chronicles\Middleware;

use Horus\Chronicles\Core\Chronicles;
use Horus\Chronicles\Events\HttpEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class HttpLoggerMiddleware
 *
 * Middleware PSR-15 para captura automática de eventos HTTP.
 * Ele mede o tempo de resposta, captura dados da requisição e da resposta
 * e despacha um HttpEvent para o Chronicles.
 */
class HttpLoggerMiddleware implements MiddlewareInterface
{
    /**
     * Processa uma requisição e gera uma resposta, registrando o evento.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);

        // Delega para o próximo middleware ou para o handler final da aplicação
        $response = $handler->handle($request);

        $end = microtime(true);

        if (Chronicles::isWatcherEnabled('http')) {
            $event = new HttpEvent(
                $request->getMethod(),
                (string) $request->getUri(),
                $response->getStatusCode(),
                ($end - $start) * 1000, // em milissegundos
                $request->getServerParams()['REMOTE_ADDR'] ?? null,
                $request->getHeaders(),
                $this->getPayloadFromRequest($request),
                $this->getPayloadFromResponse($response)
            );

            Chronicles::dispatch($event);
        }

        return $response;
    }

    /**
     * Extrai o corpo da requisição de forma segura.
     * @param ServerRequestInterface $request
     * @return array
     */
    private function getPayloadFromRequest(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            return json_decode((string) $request->getBody(), true) ?? [];
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded') || str_contains($contentType, 'multipart/form-data')) {
            return $request->getParsedBody() ?? [];
        }

        return [];
    }

    /**
     * Extrai o corpo da resposta de forma segura.
     * @param ResponseInterface $response
     * @return array
     */
    private function getPayloadFromResponse(ResponseInterface $response): array
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $body = $response->getBody();

        if (str_contains($contentType, 'application/json') && $body->isReadable()) {
            $bodyContents = (string) $body;
            // Reposiciona o ponteiro para o início para que a aplicação possa ler o corpo novamente
            $body->rewind();
            return json_decode($bodyContents, true) ?? [];
        }

        return [];
    }
}