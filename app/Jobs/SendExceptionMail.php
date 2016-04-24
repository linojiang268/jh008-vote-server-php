<?php
namespace Jihe\Jobs;

use Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mail;

class SendExceptionMail extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * error code exception message
     * @var string
     */
    protected $msg;

    /**
     * error code exception trace
     * @var string
     */
    protected $trace;

    /**
     * error code file
     * @var string
     */
    protected $file;

    /**
     * error code line
     * @var string
     */
    protected $line;

    /**
     * send mail to
     * 
     * @var string
     */
    protected $mailTo;

    /**
     * @param \Exception $exception
     * @param string     $mailTo
     */
    public function __construct(\Exception $exception, $mailTo)
    {
        $this->msg = $exception->getMessage();
        $this->trace = substr($exception->getTraceAsString(), 0, 2000);
        $this->file = $exception->getFile();
        $this->line = $exception->getLine();
        $this->mailTo = $mailTo;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mailTo = $this->mailTo;
        try {
            Mail::send('emails.exception', ['msg' => $this->msg, 'trace' => $this->trace, 'file' => $this->file, 'line' => $this->line],
                function ($m) use ($mailTo) {
                $m->to($mailTo, 'Developer')->subject('[' . strtoupper(app('env')) .'] Exception');
            });
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
        }
    }

}
