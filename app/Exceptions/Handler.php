<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Throwable $exception)
    {
        // Handle authentication exceptions (e.g., invalid or missing tokens)
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'status' => false,
                'message' => 'Token not found or has expired. Please authenticate again.',
            ], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        // Handle route not defined exceptions
        if ($exception instanceof RouteNotFoundException) {
            return response()->json([
                'status' => false,
                'message' => 'The route you are trying to access does not exist or requires authentication.',
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        // Handle validation exceptions
        if ($exception instanceof ValidationException) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error occurred.',
                'errors' => $exception->errors(),
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Handle resource not found exceptions
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'status' => false,
                'message' => 'Resource not found.',
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        // Handle method not allowed exceptions
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'status' => false,
                'message' => 'HTTP method not allowed for this route.',
            ], SymfonyResponse::HTTP_METHOD_NOT_ALLOWED);
        }

        // Fallback: Default response for all other exceptions
        return response()->json([
            'status' => false,
            'message' => $exception->getMessage(),
        ], $this->getStatusCode($exception));
    }

    /**
     * Get the HTTP status code for the exception.
     *
     * @param \Throwable $exception
     * @return int
     */
    protected function getStatusCode(Throwable $e): int
    {
        // Handle authentication exceptions (e.g., invalid or missing plainTextToken)
        if ($e instanceof AuthenticationException) {
            return SymfonyResponse::HTTP_UNAUTHORIZED;
        }

        // Handle validation exceptions (e.g., invalid request data)
        if ($e instanceof ValidationException) {
            return SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY;
        }

        // Handle resource not found exceptions
        if ($e instanceof NotFoundHttpException) {
            return SymfonyResponse::HTTP_NOT_FOUND;
        }

        // Handle method not allowed exceptions
        if ($e instanceof MethodNotAllowedHttpException) {
            return SymfonyResponse::HTTP_METHOD_NOT_ALLOWED;
        }

        // Default to internal server error for all other exceptions
        return SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR;
    }
}
