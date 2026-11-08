<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Redirect;

use Illuminate\Http\Exceptions\PostTooLargeException;
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'export_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
            
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof PostTooLargeException) {
             return redirect()->back()->with('error', 'The uploaded file is too large. Please upload a file smaller than the allowed size.' ); // 413 Payload Too Large
        }

        return parent::render($request, $exception);
    }
}
