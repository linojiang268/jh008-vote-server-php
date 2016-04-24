<?php
namespace Jihe\Exceptions;

use Exception;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Jihe\Dispatches\DispatchesSendExceptionMail;
use Mail;
use Jihe\Http\Responses\RespondsJson;

class Handler extends ExceptionHandler
{
    use RespondsJson, DispatchesJobs, DispatchesSendExceptionMail;
    
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Http\Exception\HttpResponseException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Jihe\Exceptions\SignatureException::class,
    ];
    
    private $wontExpose = [
        \Illuminate\Session\TokenMismatchException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        if ($this->shouldReport($e)) {
            $this->log->error($e);
            $this->dispatchSendMail($e);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $ex
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $ex)
    {
        if ($request->ajax()) {
            return $this->renderAsJson($request, $ex);
        }

        // render HttpException as request aware ones
        if ($this->isHttpException($ex)) {
            return $this->toIlluminateResponse($this->renderRequestAwareHttpException($request, $ex), $ex);
        }

        return parent::render($request, $ex);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpKernel\Exception\HttpException  $ex
     * @return \Illuminate\Http\Response
     */
    protected function renderRequestAwareHttpException($request, \Symfony\Component\HttpKernel\Exception\HttpException $ex)
    {
        $status = $ex->getStatusCode();
        $sub = $this->findPathInfoFirstComponent($request->getPathInfo());

        if (view()->exists("errors.{$status}-{$sub}")) {
            return response()->view("errors.{$status}-{$sub}", ['exception' => $ex], $status);
        }

        return $this->renderHttpException($ex);
    }

    private function findPathInfoFirstComponent($pathInfo)
    {
        $pathInfo = ltrim($pathInfo, '/') . '/';

        return substr($pathInfo, 0, strpos($pathInfo, '/'));
    }
    
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $ex
     * @return \Illuminate\Http\Response
     */
    private function renderAsJson($request, Exception $ex)
    {
        if (in_array(get_class($ex), $this->wontExpose)) {
            return $this->jsonException($ex->getMessage());
        }
        
        return $this->jsonException($ex);
    }
}
