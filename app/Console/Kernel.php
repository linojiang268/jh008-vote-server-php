<?php
namespace Jihe\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \Jihe\Console\Commands\CleanExpiredRegistrationVerifications::class,
        \Jihe\Console\Commands\SignKeyGenerateCommand::class,
        \Jihe\Console\Commands\SearchInitCommand::class,
        \Jihe\Console\Commands\ActivityBeginRemindCommand::class,
        \Jihe\Console\Commands\ReportPendingTeamMemberEnrollmentRequestsCommand::class,
        \Jihe\Console\Commands\ActivityPublishRemindToExceptTeamMembersCommand::class,
        \Jihe\Console\Commands\ReportPendingActivityAlbumImagesCommand::class,
        \Jihe\Console\Commands\EndActivityStatisticsCommand::class,
        \Jihe\Console\Commands\VoteCacheClearCommand::class,
        \Jihe\Console\Commands\NewsClickNumCountCommand::class,
        \Jihe\Console\Commands\CleanStorageTmpObjectsCommand::class,
        \Jihe\Console\Commands\UploadStaticFileToStorageCommand::class,
        \Jihe\Console\Commands\ExportVotersCountCommand::class,
        \Jihe\Console\Commands\ExportAttendantsCommand::class,
        \Jihe\Console\Commands\UserResetIdentitySaltCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//        $schedule->command('regverification:clean')
//                 ->daily();
//        $schedule->command('activity:begin:remind')
//                 ->dailyAt('12:30');
//        $schedule->command('teammember:report:pendingenrollmentrequests')
//                 ->dailyAt('22:00');
//        // not need to remind
////        $schedule->command('activity:report:pendingalbumimages')
////                 ->dailyAt('09:10');
//        $schedule->command("storage:tmp:clean -e '3 days'")
//                 ->dailyAt('03:00');
//        $schedule->command("attendant:export -l gaoxin_attendants -t yuandingshan@jh008.com")
//                 ->dailyAt('08:00');
    }
}
