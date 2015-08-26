<?php

namespace PhpSoft\Users\Middleware;

use Closure;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\ResponseFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use PhpSoft\ArrayView\Facades\ArrayView;

class Authenticate
{
    /**
     * @var \Illuminate\Routing\ResponseFactory
     */
    protected $response;

    /**
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    /**
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param \Illuminate\Routing\ResponseFactory  $response
     * @param \Illuminate\Events\Dispatcher  $events
     * @param \Tymon\JWTAuth\JWTAuth  $auth
     */
    public function __construct(ResponseFactory $response, Dispatcher $events, JWTAuth $auth)
    {
        $this->response = $response;
        $this->events = $events;
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return $this->respond('tymon.jwt.absent', 'Token is not provided.', 400);
        }

        try {
            $user = $this->auth->authenticate($token);
        } catch (TokenExpiredException $e) {
            return $this->respond('tymon.jwt.expired', 'Token has expired.', $e->getStatusCode(), [$e]);
        } catch (JWTException $e) {
            return $this->respond('tymon.jwt.invalid', 'Token is invalid.', $e->getStatusCode(), [$e]);
        }

        if (!$user) {
            return $this->respond('tymon.jwt.user_not_found', 'User not found.', 404);
        }

        $this->events->fire('tymon.jwt.valid', $user);

        return $next($request);
    }

    /**
     * Fire event and return the response
     *
     * @param  string   $event
     * @param  string   $error
     * @param  integer  $status
     * @param  array    $payload
     * @return mixed
     */
    protected function respond($event, $error, $status, $payload = [])
    {
        $response = $this->events->fire($event, $payload, true);

        return $response ?: $this->response->json(arrayView('phpsoft.users::errors/authenticate', ['error' => $error]), $status);
    }
}
