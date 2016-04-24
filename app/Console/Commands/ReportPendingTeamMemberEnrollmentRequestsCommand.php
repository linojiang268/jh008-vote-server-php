<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Services\TeamMemberService;
use Jihe\Utils\SmsTemplate;
use Symfony\Component\Console\Input\InputOption;

class ReportPendingTeamMemberEnrollmentRequestsCommand extends Command
{
    use DispatchesJobs, DispatchesMessage;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'teammember:report:pendingenrollmentrequests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report pending enrollment requests to team manager.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(TeamMemberService $memberService)
    {
        $teams = $this->input->hasOption('team') ? $this->option('team') : null;
        $stats = $memberService->statPendingEnrollmentRequests($teams);
        if (empty($stats)) {
            return;
        }

        foreach ($stats as $teamId => $stat) {
            $team = $stat['team'];

            $this->sendToUsers([$team['mobile']], [
                'content' => SmsTemplate::generalMessage(SmsTemplate::SOME_TEAM_MEMBER_ENROLLMENT_REQUEST_PENDING,
                                                         $stat['pending_requests'], $team['name'])
            ], [
                'sms' => true
            ]);
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['team', 't', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'specific team(s)', []],
        ];
    }
}
