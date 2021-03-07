<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($request->is('auth', 'auth/*', 'api', 'api/*')) {
            $response = function ($content, $status) use ($exception) {
                $response = response(array_merge($content, config('app.debug') ? [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTrace(),
                ] : []), $status);

                $response->exception = $exception;

                return $response;
            };

            if ($exception instanceof AuthenticationException) {
                return $response(['error' => 'Unauthenticated'], 401);
            }

            if ($exception instanceof ModelNotFoundException) {
                return $response(['error' => 'Not Found'], 404);
            }

            if ($exception instanceof TokenMismatchException) {
                return $response(['error' => 'CSRF Token Mismatch'], 403);
            }

            if ($exception instanceof ValidationException) {
                return $response([
                    'error' => 'Validation',
                    'validation_fields' => $exception->validator->errors(),
                ], 422);
            }

            if (method_exists($exception, 'getStatusCode')) {
                if ($exception->getStatusCode() === 401) {
                    return $response(['error' => 'Unauthenticated'], 401);
                }

                if ($exception->getStatusCode() === 403) {
                    return $response(['error' => 'Forbidden'], 403);
                }

                if ($exception->getStatusCode() === 404) {
                    return $response(['error' => 'Not Found'], 404);
                }
            }

            return $response(['error' => 'Internal Error'], 500);
        }

        return parent::render($request, $exception);
    }
}
