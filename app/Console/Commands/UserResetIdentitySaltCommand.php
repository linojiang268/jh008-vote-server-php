<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Models\User;
use Jihe\Repositories\UserRepository;
use Jihe\Services\UserService;
use Jihe\Utils\PaginationUtil;
use Symfony\Component\Console\Input\InputOption;

class UserResetIdentitySaltCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'user:identity:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset identity salt of user.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(UserService $userService)
    {
        $user = $this->input->hasOption('user') ? $this->option('user') : null;

        try {
            if (is_null($user)) {
                list($count, $users) = $userService->listUsers(1, 50);
                $pages = PaginationUtil::count2Pages($count, 50);
                $this->resetIdentityOfUsers($users, $userService);

                for ($page = 2; $page <= $pages; $page++) {
                    list($count, $users) = $userService->listUsers($page, 50);
                    $this->resetIdentityOfUsers($users, $userService);
                }
                echo "reset identity of all users successfully\n";
                return;
            }

            $user = $userService->findUserById($user);
            if (!user) {
                echo "reset identity of all users failed: user is not exists\n";
                return;
            }
            $this->resetIdentityOfUsers([$user], $userService);
            echo "reset identity of user " . $user . " successfully\n";
        } catch (\Exception $e) {
            echo "reset identity of all users failed: " . $e->getTraceAsString() . "\n";
        }
    }

    private function resetIdentityOfUsers(array $users, UserService $userService)
    {
        foreach ($users as $user) {
            $userService->resetIdentity($user);
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
            ['user', 'u', InputOption::VALUE_OPTIONAL, 'special id of user.',  null],
        ];
    }
}