<?php
namespace spec\Jihe\Services;

use Illuminate\Support\Facades\Bus;
use Jihe\Contracts\Repositories\LoginDeviceRepository;
use Jihe\Services\TeamService;
use Prophecy\Argument;
use \PHPUnit_Framework_Assert as Assert;
use Jihe\Contracts\Repositories\MessageRepository;
use Jihe\Services\TeamMemberService;
use Jihe\Services\ActivityMemberService;
use Jihe\Services\UserService;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\Message as MessageEntity;
use Jihe\Entities\TeamMember as TeamMemberEntity;
use Jihe\Services\Push\YunbaPushService;
use Jihe\Services\MessageService;
use PhpSpec\Laravel\LaravelObjectBehavior;

class MessageServiceSpec extends LaravelObjectBehavior
{
    function let(MessageRepository $messageRepository,
                 TeamMemberService $teamMemberService,
                 ActivityMemberService $activityMemberService,
                 UserService $userService,
                 LoginDeviceRepository $loginDeviceRepository,
                 TeamService $teamService)
    {
        $this->beAnInstanceOf(\Jihe\Services\MessageService::class, [
            $messageRepository,
            $teamMemberService,
            $activityMemberService,
            $userService,
            $loginDeviceRepository,
            $teamService,
        ]);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\PushToTeamMessageJob);
        }))->andReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\PushToActivityMessageJob);
        }))->andReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\PushToAliasMessageJob);
        }))->andReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\PushToTopicMessageJob);
        }))->andReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\SendSms);
        }))->andReturn(null);
    }
    
    //=============================================
    //                SendToUsers
    //=============================================
    function it_sends_to_all_users_successful(MessageRepository $messageRepository,
                                          UserService $userService)
    {
        $message = (new MessageEntity())->setId(1);
    
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                return self::assertMessage(
                                    'user message content',
                                    'text',
                                    null,
                                    MessageEntity::NOTIFIED_TYPE_SMS,
                                    null,
                                    null,
                                    null,
                                    $message);
                            }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        $userService->listUsers(
                        Argument::that(function ($page) {
                            return $page >= 1 && $page <= 3;
                        }), 
                        MessageService::SMS_SUBSECTION_COUNT)
                    ->shouldBeCalled()
                    ->willReturn([
                        21,
                        [(new UserEntity())->setMobile('13812345678')],
                    ]);
        
                            
        $this->sendToUsers(null, [
            'content' => 'user message content',
            'type'    => 'text',
        ], [
            'record'  => true,
            'push'    => true,
            'sms'     => true,
        ]);
    }
    
    function it_sends_to_special_users_successful(MessageRepository $messageRepository,
                                                  UserService $userService, LoginDeviceRepository $loginDeviceRepository)
    {
        $message = (new MessageEntity())->setId(1);
    
        $userService->fetchUsersForMessagePush(['13812345678', '13812345679'])
                    ->shouldBeCalled()
                    ->willReturn(['13812345678' => 1, '13812345679' => 2]);
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                return self::assertMessage(
                                    'user message content',
                                    'text',
                                    null,
                                    MessageEntity::NOTIFIED_TYPE_SMS,
                                    null,
                                    null,
                                    1,
                                    $message);
                            }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                return self::assertMessage(
                                    'user message content',
                                    'text',
                                    null,
                                    MessageEntity::NOTIFIED_TYPE_SMS,
                                    null,
                                    null,
                                    2,
                                    $message);
                            }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        $loginDeviceRepository->findClientIdentifiers(['13812345678', '13812345679'])
                              ->shouldBeCalled()
                              ->willReturn(['13812345678' => 'alias1', '13812345679' => 'alias2']);
                            
        $this->sendToUsers(['13812345678', '13812345679'], [
                            'content' => 'user message content',
                            'type'    => 'text',
                        ], [
                            'record'  => true,
                            'push'    => true,
                            'sms'     => true,
                        ]);
    }
    
    //=============================================
    //                SendToTeamMembers
    //=============================================
    function it_sends_to_all_team_members_successful(MessageRepository $messageRepository,
                                                     TeamMemberService $teamMemberService)
    {
        $team = (new TeamEntity())->setId(1);
        $message = (new MessageEntity())
                    ->setId(1)
                    ->setTeam($team);
    
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                return self::assertMessage(
                                    'team member message content',
                                    MessageEntity::TYPE_TEAM,
                                    ['team_id' => 1],
                                    MessageEntity::NOTIFIED_TYPE_SMS,
                                    1,
                                    null,
                                    null,
                                    $message);
                            }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        $teamMemberService->listMembers(
                            1,
                            Argument::that(function ($page) {
                                return $page >= 1 && $page <= 3;
                            }),
                            MessageService::SMS_SUBSECTION_COUNT)
                            ->shouldBeCalled()
                            ->willReturn([
                                            21,
                                            [(new TeamMemberEntity())
                                                ->setTeam((new TeamEntity())->setId(1))
                                                ->setUser((new UserEntity())->setMobile('13812345678'))],
                                        ]);
                    
        $this->sendToTeamMembers($team, null, [
            'content'    => 'team member message content',
        ], [
            'record'  => true,
            'push'    => true,
            'sms'     => true,
        ]);
    }
    
    function it_sends_to_special_team_members_successful(MessageRepository $messageRepository, 
                                                         UserService $userService, LoginDeviceRepository $loginDeviceRepository)
    {
        $team = (new TeamEntity())->setId(1);
        $message = (new MessageEntity())
                        ->setId(1)
                        ->setTeam($team);
    
        $userService->fetchUsersForMessagePush(['13812345678', '13812345679'])
                    ->shouldBeCalled()
                    ->willReturn(['13812345678' => 1, '13812345679' => 2]);
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                    return self::assertMessage(
                                        'team member message content',
                                        'text',
                                        null,
                                        MessageEntity::NOTIFIED_TYPE_SMS,
                                        1,
                                        null,
                                        1,
                                        $message);
                                }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                    return self::assertMessage(
                                        'team member message content',
                                        'text',
                                        null,
                                        MessageEntity::NOTIFIED_TYPE_SMS,
                                        1,
                                        null,
                                        2,
                                        $message);
                                }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        $loginDeviceRepository->findClientIdentifiers(['13812345678', '13812345679'])
                              ->shouldBeCalled()
                              ->willReturn(['13812345678' => 'alias1', '13812345679' => 'alias2']);
    
        $this->sendToTeamMembers($team, ['13812345678', '13812345679'], [
            'content' => 'team member message content',
            'type'    => MessageEntity::TYPE_TEXT,
        ], [
            'record'  => true,
            'push'    => true,
            'sms'     => true,
        ]);
    }
    
    //=============================================
    //            SendToActivityMembers
    //=============================================
    function it_sends_to_all_activity_members_successful(MessageRepository $messageRepository,
                                                         ActivityMemberService $activityMemberService)
    {
        $activity = (new ActivityEntity())
                        ->setId(1)
                        ->setTeam((new TeamEntity())->setId(1));
        $message = (new MessageEntity())
                        ->setId(1)
                        ->setActivity($activity);
    
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                return self::assertMessage(
                                    'activity member message content',
                                    'text',
                                    null,
                                    MessageEntity::NOTIFIED_TYPE_SMS,
                                    1,
                                    1,
                                    null,
                                    $message);
                            }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        $activityMemberService->getActivityMemberList(
                                1,
                                Argument::that(function ($page) {
                                    return $page >= 1 && $page <= 3;
                                }),
                                MessageService::SMS_SUBSECTION_COUNT)
                              ->shouldBeCalled()
                              ->willReturn([
                                    21,
                                    [['mobile' => '13812345678']],
                                ]);
    
        $this->sendToActivityMembers($activity, null, [
            'content' => 'activity member message content',
            'type'    => 'text',
        ], [
            'record'  => true,
            'push'    => true,
            'sms'     => true,
        ]);
    }
    
    function it_sends_to_special_activity_members_successful(MessageRepository $messageRepository,
                                                             UserService $userService, LoginDeviceRepository $loginDeviceRepository)
    {
        $activity = (new ActivityEntity())
                        ->setId(1)
                        ->setTeam((new TeamEntity())->setId(1));
        $message = (new MessageEntity())
                        ->setId(1)
                        ->setActivity($activity);
    
        $userService->fetchUsersForMessagePush(['13812345678', '13812345679'])
                    ->shouldBeCalled()
                    ->willReturn(['13812345678' => 1, '13812345679' => 2]);
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                    return self::assertMessage(
                                        'activity member message content',
                                        MessageEntity::TYPE_ACTIVITY,
                                        ['activity_id' => 1],
                                        MessageEntity::NOTIFIED_TYPE_SMS,
                                        1,
                                        1,
                                        1,
                                        $message);
                                }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                    return self::assertMessage(
                                        'activity member message content',
                                        MessageEntity::TYPE_ACTIVITY,
                                        ['activity_id' => 1],
                                        MessageEntity::NOTIFIED_TYPE_SMS,
                                        1,
                                        1,
                                        2,
                                        $message);
                                }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        $loginDeviceRepository->findClientIdentifiers(['13812345678', '13812345679'])
                              ->shouldBeCalled()
                              ->willReturn(['13812345678' => 'alias1', '13812345679' => 'alias2']);
    
        $this->sendToActivityMembers($activity, ['13812345678', '13812345679'], [
            'content'    => 'activity member message content',
        ], [
            'record'  => true,
            'push'    => true,
            'sms'     => true,
        ]);
    }
    
    //=============================================
    //             RecordSystemMessage
    //=============================================
    function it_records_system_message_successful(MessageRepository $messageRepository)
    {
        $message = (new MessageEntity())->setId(1);
        
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                            return self::assertMessage(
                                       'system msg', 
                                       'text', 
                                       null,
                                       MessageEntity::NOTIFIED_TYPE_PUSH,
                                       null, 
                                       null, 
                                       null, 
                                       $message);
                          }))
                          ->shouldBeCalled()
                          ->willReturn($message);
        
        $this->record('system msg')->shouldBe($message);
    }
    
    private static function assertMessage($expectedContent, $expectedType, $expectedAttributes,
                                          $expectedNotifiedType, $expectedTeamId, $expectedActivityId,
                                          $expectedUserId, MessageEntity $message)
    {
        return $expectedContent == $message->getContent() &&
               $expectedType == $message->getType() &&
               $expectedAttributes == $message->getAttributes() &&
               $expectedNotifiedType == $message->getNotifiedType() &&
               ((null == $expectedTeamId && null == $message->getTeam()) || 
                ($expectedTeamId == $message->getTeam()->getId())) && 
               ((null == $expectedActivityId && null == $message->getActivity()) ||
                ($expectedActivityId == $message->getActivity()->getId())) &&
               ((null == $expectedUserId && null == $message->getUser()) ||
                ($expectedUserId == $message->getUser()->getId()));
    }
    
    //=============================================
    //                RecordTeamMessage
    //=============================================
    function it_records_team_message_successful(MessageRepository $messageRepository)
    {
        $message = (new MessageEntity())->setId(1);
        $team = (new TeamEntity())->setId(1);
    
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                return self::assertMessage(
                                    'team notice',
                                    MessageEntity::TYPE_TEAM,
                                    ['team_id' => 1],
                                    MessageEntity::NOTIFIED_TYPE_PUSH,
                                    1,
                                    null,
                                    null,
                                    $message);
                            }))
                          ->shouldBeCalled()
                          ->willReturn($message);
    
        $this->record('team notice', MessageEntity::TYPE_TEAM, ['team_id' => 1], MessageEntity::NOTIFIED_TYPE_PUSH, 1)
             ->shouldBe($message);
    }
    
    //=============================================
    //                RecordActivityMessage
    //=============================================
    function it_records_activity_message_successful(MessageRepository $messageRepository)
    {
        $message = (new MessageEntity())->setId(1);
        $activity = (new ActivityEntity())->setId(1)
                    ->setTeam((new TeamEntity())->setId(1));
    
        $messageRepository->add(Argument::that(function (MessageEntity $message) {
                                return self::assertMessage(
                                    'activity notice',
                                    'activity',
                                    ['activity_id' => 1],
                                    MessageEntity::NOTIFIED_TYPE_SMS,
                                    1,
                                    1,
                                    null,
                                    $message);
                            }))
                          ->shouldBeCalled()
                          ->willReturn($message);
    
        $this->record('activity notice', MessageEntity::TYPE_ACTIVITY, ['activity_id' => 1], MessageEntity::NOTIFIED_TYPE_SMS, 1, 1)
             ->shouldBe($message);
    }
}