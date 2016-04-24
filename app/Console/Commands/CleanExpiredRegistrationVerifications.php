<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Services\VerificationService;

class CleanExpiredRegistrationVerifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regverification:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired verifications for registration';

    /**
     * Execute the console command.
     *
     * @param VerificationService $service
     */
    public function handle(VerificationService $service)
    {
        $service->removeExpiredVerificationsForRegistration();
    }
}
