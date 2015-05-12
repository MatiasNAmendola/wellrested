<?php

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;

/**
 * Dispatches an ordered sequence of middleware.
 */
interface DispatchStackInterface extends MiddlewareInterface
{
    /**
     * Push a new middleware onto the stack.
     *
     * This method MUST preserve the order in which middleware added.
     *
     * @param mixed $middleware Middleware to dispatch in sequence
     * @return self
     */
    public function add($middleware);

    /**
     * Dispatch the contained middleware in the order in which they were added.
     *
     * The first middleware added to the stack MUST be dispatched first.
     *
     * Each middleware, when dispatched, MUST receive a $next callable that
     * dispatches the middleware that follows it, unless it is the last
     * middleware. The last middleware MUST receive a $next callable that
     * returns the response unchanged.
     *
     * When any middleware does not call the $next argument it recieved, the
     * stack instance MUST stop propogating through the stack and MUST return
     * the response without calling the $next argument passed to dispatch.
     *
     * This method MUST call the passed $next argument when:
     * - The stack is empty (i.e., there is no middleware to dispatch)
     * - Each middleware called the $next that it receieved.
     *
     * This method MUST NOT call the passed $next argument when the stack is
     * not empty and any middleware returns a response without calling the
     * $next it receieved.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next);
}
