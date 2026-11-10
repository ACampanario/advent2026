<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Exception\TooManyRequestsException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Turns the 429 thrown by RateLimitMiddleware into a flash message and a redirect.
 *
 * The chat form is a plain (non-AJAX) POST, so an error page would break the chat.
 * The user is sent back to the conversation with the technical details in the flash.
 */
class RateLimitFlashMiddleware implements MiddlewareInterface
{
    /**
     * @param int $limit Allowed requests per window (shown in the flash).
     * @param int $window Window length in seconds (shown in the flash).
     */
    public function __construct(
        private readonly int $limit,
        private readonly int $window,
    ) {
    }

    /**
     * Catch the rate limit exception and redirect back with a flash message.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (TooManyRequestsException $exception) {
            if (!$request instanceof ServerRequest) {
                throw $exception;
            }

            $retryAfter = (string)($exception->getHeaders()['Retry-After'] ?? $this->window);
            $request->getFlash()->error(__(
                'Too many messages (HTTP {0}): limit {1} per {2} s. Try again in {3} s.',
                $exception->getCode(),
                $this->limit,
                $this->window,
                $retryAfter,
            ));

            $conversationId = filter_var($request->getData('conversation_id'), FILTER_VALIDATE_INT);
            $url = $conversationId !== false
                ? Router::url(['controller' => 'Conversations', 'action' => 'view', $conversationId])
                : Router::url(['controller' => 'Conversations', 'action' => 'index']);

            return (new Response())->withLocation($url)->withStatus(302);
        }
    }
}
