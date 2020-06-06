<?php

namespace App\Exceptions;

use Throwable;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
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
        $message = 'An unkown error ocurred';
        $error_number = Response::HTTP_INTERNAL_SERVER_ERROR;

        switch(get_class($exception)) {
            case NotFoundHttpException::class:
                $message = 'The given route was not found';
                $error_number = Response::HTTP_NOT_FOUND;
            break;

            case ModelNotFoundException::class:

                $model = 'object';
                
                // Avoid 'getModel()' method error display
                if ($exception instanceof ModelNotFoundException) {
                    $model = (new ReflectionClass($exception->getModel()))->getShortName();
                }

                $message = 'The given '. $model .' does not exist.';
                $error_number = Response::HTTP_NOT_FOUND;
            break;

            case MethodNotAllowedHttpException::class:
                $message = 'Wrong method {' . $request->method() . '}';
                $error_number = Response::HTTP_METHOD_NOT_ALLOWED;
            break;

            case TooManyRequestsHttpException::class:
                $message = 'Too many requests';
                $error_number = Response::HTTP_TOO_MANY_REQUESTS;
            break;

            case ThrottleRequestsException::class:
                $message = 'Too many attempts';
                $error_number = Response::HTTP_TOO_MANY_REQUESTS;
            break;

            case AuthenticationException::class:
                return $this->unauthenticated($request, $exception);

            case InvalidSignatureException::class:
                return $this->invalid_signature($request, $exception);

            default:
                Log::channel('single')->error($exception);
            break;
        }

        return response()->json([
            'message' => $message,
            'is_secure' => $request->secure(),
            'user_agent' => $request->userAgent(),
            'timestamp' => Carbon::now(),
            'path' => $request->fullUrl(),
        ], $error_number);

    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'errors' => [
                'Unauthenticated'
                ]
        ], Response::HTTP_UNAUTHORIZED);
    }

    protected function invalid_signature($request, InvalidSignatureException $exception)
    {
        return response()->json([
            'errors' => [
                    'Invalid signature'
                ]
        ], Response::HTTP_UNAUTHORIZED);
    }
}
