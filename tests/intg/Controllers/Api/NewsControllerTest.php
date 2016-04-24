<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Models\Team;
use Jihe\Models\User;

use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;

class NewsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //           getTeamNews
    //=========================================
    public function testGetTeamNews()
    {
        $user = factory(User::class)->create();
        factory(Team::class)->create([
            'id' => 1,
        ]);

        $this->startSession();

        $this->actingAs($user)->ajaxGet('/api/news?team_id=1');
        $this->seeJsonContains([ 'code' => 0, 'pages' => 0, 'news' => [] ]);
    }

}
