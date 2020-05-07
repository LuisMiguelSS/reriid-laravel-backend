<?php

namespace App\Exceptions;

use Throwable;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
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
     * @param  Throwable  $exception
     * @return void
     *
     * @throws Throwable
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @param  Throwable  $exception
     * 
     * @return Response
     * @throws Throwable
     *
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof NotFoundHttpException || $exception instanceof ModelNotFoundException | $exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'message' => 'The requested resource was not found',
                'timestamp' => Carbon::now(),
                'path' => $request->fullUrl()
            ], Response::HTTP_NOT_FOUND);

        } else if ($exception instanceof TooManyRequestsHttpException) {
            return response()->json([
                'message' => 'Too many requests',
                'timestamp' => Carbon::now(),
                'headers' => $exception->getHeaders()
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'errors' => [
                'Unauthenticated'
                ]
        ], Response::HTTP_UNAUTHORIZED);
    }
}
