<?php
namespace intg\Jihe\Repositories;

use intg\Jihe\TestCase;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\Message as MessageEntity;

class MessageRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=======================================
    //                 Add
    //=======================================
    public function testAdd()
    {
        $systemMessage = (new MessageEntity())
                            ->setContent('system message')
                            ->setType('text');
        
        $teamNotice = (new MessageEntity())
                        ->setTeam((new TeamEntity)->setId(1))
                        ->setContent('team notice')
                        ->setType('text');
        
        $teamNoticeForUrl = (new MessageEntity())
                                ->setTeam((new TeamEntity)->setId(1))
                                ->setContent('team notice for url')
                                ->setType('url')
                                ->setAttributes(["url" => "http://page.html"]);
        
        $teamNoticeOfUser = (new MessageEntity())
                                ->setTeam((new TeamEntity)->setId(1))
                                ->setUser((new UserEntity())->setId(1))
                                ->setContent('team notice of user')
                                ->setType('text');
                        
        $activityNotice = (new MessageEntity())
                            ->setTeam((new TeamEntity)->setId(1))
                            ->setActivity((new ActivityEntity())->setId(1))
                            ->setContent('activity notice')
                            ->setType('text');
        
        $activityNoticeOfUser = (new MessageEntity())
                                    ->setTeam((new TeamEntity)->setId(1))
                                    ->setActivity((new ActivityEntity())->setId(1))
                                    ->setUser((new UserEntity())->setId(1))
                                    ->setContent('activity notice of user')
                                    ->setType('text');
        
        Assert::assertNotFalse($this->getRepository()->add($systemMessage));
        Assert::assertNotFalse($this->getRepository()->add($teamNotice));
        Assert::assertNotFalse($this->getRepository()->add($teamNoticeForUrl));
        Assert::assertNotFalse($this->getRepository()->add($teamNoticeOfUser));
        Assert::assertNotFalse($this->getRepository()->add($activityNotice));
        Assert::assertNotFalse($this->getRepository()->add($activityNoticeOfUser));
        
        $this->seeInDatabase('messages', [
            'team_id' => null,
            'activity_id' => null,
            'user_id' => null,
            'content'  => 'system message',
            'type' => 'text',
            'attributes' => null,
        ]);
        
        $this->seeInDatabase('messages', [
            'team_id' => 1,
            'activity_id' => null,
            'user_id' => null,
            'content'  => 'team notice',
            'type' => 'text',
            'attributes' => null,
        ]);
        
        $this->seeInDatabase('messages', [
            'team_id' => 1,
            'activity_id' => null,
            'user_id' => null,
            'content'  => 'team notice for url',
            'type' => 'url',
        ]);
        
        $this->seeInDatabase('messages', [
            'team_id' => 1,
            'activity_id' => null,
            'user_id' => 1,
            'content'  => 'team notice of user',
            'type' => 'text',
            'attributes' => null,
        ]);
        
        $this->seeInDatabase('messages', [
            'team_id' => 1,
            'activity_id' => 1,
            'user_id' => null,
            'content'  => 'activity notice',
            'type' => 'text',
            'attributes' => null,
        ]);
        
        $this->seeInDatabase('messages', [
            'team_id' => 1,
            'activity_id' => 1,
            'user_id' => 1,
            'content'  => 'activity notice of user',
            'type' => 'text',
            'attributes' => null,
        ]);
    }
    
    //=======================================
    //          FindSystemMessagesOf
    //=======================================
    public function testFindSystemMessagesOf()
    {
        factory(\Jihe\Models\Message::class)->create([
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'created_at' => '2015-07-30 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'user_id' => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'user_id' => 2,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'user_id' => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
    
        list($total_num, $messages) = $this->getRepository()->findSystemMessagesOf(1, null, null, '2015-08-01 00:00:00');
        Assert::assertEquals(2, $total_num);
        Assert::assertCount(2, $messages);
    }
    
    //=======================================
    //           FindUserMessages
    //=======================================
    public function testFindUserMessages()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'name' => 'shetuan',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);
        
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 2,
            'city_id' => 1,
            'name' => 'shetuan2',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 3,
            'city_id' => 1,
            'name' => 'shetuan3',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);
        
        factory(\Jihe\Models\Activity::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        
        factory(\Jihe\Models\Activity::class)->create([
            'id'      => 2,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        
        factory(\Jihe\Models\Message::class)->create([
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'user_id' => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'user_id' => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'user_id' => 2,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 2,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 2,
            'user_id' => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 3,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 3,
            'user_id' => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'activity_id' => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'activity_id' => 2,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'activity_id' => 3,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'activity_id' => 1,
            'user_id' => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        list($toal_num, $messages) = $this->getRepository()->findUserMessages(null, null, 1, null, null, '2015-08-01 00:00:00');
        Assert::assertCount(4, $messages);
    
        list($toal_num, $messages) = $this->getRepository()->findUserMessages([1, 2], null, 1, null, null, '2015-08-01 00:00:00');
        Assert::assertCount(6, $messages);
        
        list($toal_num, $messages) = $this->getRepository()->findUserMessages(null, [1, 2], 1, null, null, '2015-08-01 00:00:00');
        Assert::assertCount(6, $messages);
        
        list($toal_num, $messages) = $this->getRepository()->findUserMessages([1, 2], [1, 2], 1, null, null, '2015-08-01 00:00:00');
        Assert::assertCount(8, $messages);
        
        // findUserMessageOf
        list($toal_num, $messages) = $this->getRepository()->findUserMessagesOf(1, [1, 2], 1, null, null, '2015-08-01 00:00:00');
        Assert::assertCount(5, $messages);
    }
    
    //=======================================
    //          FindSystemMessages
    //=======================================
    public function testFindSystemMessages()
    {
        factory(\Jihe\Models\Message::class)->create([]);
        factory(\Jihe\Models\Message::class)->create([]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'user_id' => 1,
        ]);
    
        list($total_num, $messages) = $this->getRepository()->findSystemMessages(1, 10);
        Assert::assertEquals(2, $total_num);
        Assert::assertCount(2, $messages);
    }
    
    //=======================================
    //          FindTeamMessages
    //=======================================
    public function testFindTeamMessages()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'name' => 'shetuan',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);
        
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
            'user_id' => 1,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 2,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => 1,
        ]);
    
        list($total_num, $messages) = $this->getRepository()->findTeamMessages(1, 1, 10);
        Assert::assertEquals(2, $total_num);
        Assert::assertCount(2, $messages);
    }
    
    //=======================================
    //          FindActivityMessages
    //=======================================
    public function testFindActivityMessages()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'name' => 'shetuan',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);
        
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => 1,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => 1,
        ]);
        factory(\Jihe\Models\Message::class)->create([]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id' => 1,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => 2,
        ]);
    
        list($total_num, $messages) = $this->getRepository()->findActivityMessages(1, 1, 10);
        Assert::assertEquals(2, $total_num);
        Assert::assertCount(2, $messages);
    }

    //=======================================
    //           FindMessagesOfUser
    //=======================================
    public function testFindMessagesOfUser()
    {
        // init system messages
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 2,
            'created_at' => '2015-07-30 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 3,
            'user_id'    => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 4,
            'user_id'    => 2,
            'created_at' => '2015-08-03 00:00:00',
        ]);

        // init 3 teams
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'name' => 'shetuan',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 2,
            'city_id' => 1,
            'name' => 'shetuan2',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 3,
            'city_id' => 1,
            'name' => 'shetuan3',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);

        // init 2 activities
        factory(\Jihe\Models\Activity::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'      => 2,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);

        // init team messages
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 11,
            'team_id'    => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 12,
            'team_id'    => 1,
            'user_id'    => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 13,
            'team_id'    => 1,
            'user_id'    => 2,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 14,
            'team_id'    => 2,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 15,
            'team_id'    => 2,
            'user_id'    => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 16,
            'team_id'    => 3,
            'created_at' => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 17,
            'team_id'    => 3,
            'user_id'    => 1,
            'created_at' => '2015-08-03 00:00:00',
        ]);

        //init activity messages
        factory(\Jihe\Models\Message::class)->create([
            'id'          => 21,
            'team_id'     => 1,
            'activity_id' => 1,
            'created_at'  => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'          => 22,
            'team_id'     => 1,
            'activity_id' => 2,
            'created_at'  => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'          => 23,
            'team_id'     => 1,
            'activity_id' => 3,
            'created_at'  => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'          => 24,
            'team_id'     => 1,
            'activity_id' => 1,
            'user_id'     => 1,
            'created_at'  => '2015-08-03 00:00:00',
        ]);

        // 2 system messages + 3 team messages + 1 activity messages
        Assert::assertEquals(6, $this->getRepository()->findMessagesOfUser(1, [], [], '2015-08-01 00:00:00'));
        list($total, $messages) = $this->getRepository()->findMessagesOfUser(1, [], [], '2015-08-01 00:00:00', false);
        Assert::assertEquals(6, $total);
        Assert::assertCount(6, $messages);

        // 2 system messages + 5 team messages + 1 activity messages
        Assert::assertEquals(8, $this->getRepository()->findMessagesOfUser(1, [1, 2], [], '2015-08-01 00:00:00'));
        list($total, $messages) = $this->getRepository()->findMessagesOfUser(1, [1, 2], [], '2015-08-01 00:00:00', false);
        Assert::assertEquals(8, $total);
        Assert::assertCount(8, $messages);

        // 2 system messages + 3 team messages + 3 activity messages
        Assert::assertEquals(8, $this->getRepository()->findMessagesOfUser(1, [], [1, 2], '2015-08-01 00:00:00'));
        list($total, $messages) = $this->getRepository()->findMessagesOfUser(1, [], [1, 2], '2015-08-01 00:00:00', false);
        Assert::assertEquals(8, $total);
        Assert::assertCount(8, $messages);

        // 2 system messages + 5 team messages + 3 activity messages
        Assert::assertEquals(10, $this->getRepository()->findMessagesOfUser(1, [1, 2], [1, 2], '2015-08-01 00:00:00'));
        list($total, $messages) = $this->getRepository()->findMessagesOfUser(1, [1, 2], [1, 2], '2015-08-01 00:00:00', false);
        Assert::assertEquals(10, $total);
        Assert::assertCount(10, $messages);
    }

    //=======================================
    //         countActivityNotices
    //=======================================
    public function testCountActivityNotices()
    {
        // init 1 team
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'name' => 'shetuan',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);

        // init 2 activities
        factory(\Jihe\Models\Activity::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'      => 2,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);

        //init activity messages
        factory(\Jihe\Models\Message::class)->create([
            'id'          => 21,
            'team_id'     => 1,
            'activity_id' => 1,
            'user_id'     => null,
            'created_at'  => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'          => 22,
            'team_id'     => 1,
            'activity_id' => 2,
            'user_id'     => null,
            'created_at'  => '2015-08-03 00:00:00',
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'id'          => 24,
            'team_id'     => 1,
            'activity_id' => 1,
            'user_id'     => 1,
            'created_at'  => '2015-08-03 00:00:00',
        ]);

        Assert::assertEquals(1, $this->getRepository()->countActivityNotices(1, ['only_mass' => true]));
        Assert::assertEquals(2, $this->getRepository()->countActivityNotices(1, ['only_mass' => false]));
        Assert::assertEquals(2, $this->getRepository()->countActivityNotices(1));
    }

    /**
     * @return \Jihe\Contracts\Repositories\MessageRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\MessageRepository::class];
    }
}
