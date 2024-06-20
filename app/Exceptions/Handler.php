<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof NotFoundHttpException) {
            return $this->handleNotFound($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Handle a 404 Not Found exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpKernel\Exception\NotFoundHttpException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function handleNotFound($request, NotFoundHttpException $exception)
    {
        if ($request->acceptsJson()) {
            throw new NotFoundException($exception->getMessage());
        }

        return response()->view('errors.404', [], 404);
    }
}
