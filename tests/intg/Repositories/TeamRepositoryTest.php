<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Models\Team;
use Jihe\Models\User;
use Jihe\Models\City;
use Jihe\Models\TeamRequirement;
use Jihe\Models\TeamCertification;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\City as CityEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\TeamRequirement as TeamRequirementEntity;
use Jihe\Entities\TeamCertification as TeamCertificationEntity;

class TeamRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //===========================================
    //       getNumberOfTeamsCreatedBy
    //===========================================
    public function testGetNumberOfTeamsCreatedBy_OnlyOneTeamCreated()
    {
        factory(Team::class)->create([
            'creator_id'   => 1,
            'status'       => TeamEntity::STATUS_NORMAL,
        ]);
        
        self::assertEquals(1, $this->getRepository()->getNumberOfTeamsCreatedBy(1));
    }
    
    public function testGetNumberOfTeamsCreatedBy_TwoTeamCreatedButOneDeleted()
    {
        factory(Team::class)->create([
            'creator_id'   => 1,
            'status'       => TeamEntity::STATUS_NORMAL
        ]);
        
        factory(Team::class)->create([
            'creator_id'   => 1,
            'status'       => TeamEntity::STATUS_NORMAL,
            'deleted_at'   => '2015-02-01 00:00:00',
        ]);
        
        self::assertEquals(1, $this->getRepository()->getNumberOfTeamsCreatedBy(1));
    }
    
    public function testGetNumberOfTeamsCreatedBy_NoTeamCreated()
    {
        self::assertEquals(0, $this->getRepository()->getNumberOfTeamsCreatedBy(1));
    }
    
    //===========================================
    //              findTeam
    //===========================================
    public function testFindTeam_TeamExists()
    {
        factory(Team::class)->create([
            'id'         => 1,
            'creator_id' => 1,
            'city_id'    => 1,
        ]);
        
        factory(User::class)->create([
            'id' => 1,
        ]);
        
        factory(City::class)->create([
            'id' => 1,
        ]);
        
        factory(TeamRequirement::class)->create([
            'id' => 1,
            'team_id' => 1,
        ]);
        
        $team = $this->getRepository()->findTeam(1, ['city', 'creator', 'requirements']);
        self::assertTeam(1, 1, 1, $team);
    }
    
    public function testFindTeam_TeamNotExists()
    {
        self::assertNull($this->getRepository()->findTeam(1));
    }
    
    private static function assertTeam($expectedId, $expectedCreatorId, $expectedCityId, TeamEntity $team)
    {
        self::assertNotNull($team);
        self::assertEquals($expectedId, $team->getId());
        self::assertEquals($expectedCreatorId, $team->getCreator()->getId());
        self::assertEquals($expectedCityId, $team->getCity()->getId());
    }
    
    //===========================================
    //             findTeamsByCreator
    //===========================================
    public function testFindTeamsByCreator_TeamHasCreated()
    {
        factory(Team::class)->create([
            'id'         => 1,
            'creator_id' => 1,
            'city_id'    => 1,
        ]);
    
        factory(User::class)->create([
            'id' => 1,
        ]);
    
        factory(City::class)->create([
            'id' => 1,
        ]);
    
        $teams = $this->getRepository()->findTeamsCreatedBy(1, ['city']);
        self::assertCount(1, $teams);
        self::assertTeam(1, 1, 1, $teams[0]);
    }
    
    public function testFindTeamsByCreator_NoTeamHasCreated()
    {
        self::assertEmpty($this->getRepository()->findTeamsCreatedBy(1));
    }
    
    //===========================================
    //                findTeams
    //===========================================
    public function testFindTeams_AllTeams()
    {
        for ($i = 0; $i < 5; $i++) {
            factory(Team::class)->create([
                'id'         => $i + 1,
                'creator_id' => 1,
                'city_id'    => 1,
            ]);
        }
    
        list($pages, $teams) = $this->getRepository()->findTeams(1, 10, [], ['city' => 1]);
        
        self::assertEquals(1, $pages);
        self::assertCount(5, $teams);
        for ($i = 0; $i < 5; $i++) {
            self::assertTeam(5 - $i, 1, 1, $teams[$i]);
        }
    }
    
    public function testFindTeams_SecondPageTeams()
    {
        for ($i = 0; $i < 5; $i++) {
            factory(Team::class)->create([
                'id'         => $i + 1,
                'creator_id' => 1,
                'city_id'    => 1,
            ]);
        }
        
        list($pages, $teams) = $this->getRepository()->findTeams(2, 3, [], ['city' => 1]);
        
        self::assertEquals(2, $pages);
        self::assertCount(2, $teams);
        for ($i = 0; $i < 2; $i++) {
            self::assertTeam(2 - $i, 1, 1, $teams[$i]);
        }
    }
    
    public function testFindTeams_NoTeams()
    {
        list($pages, $teams) = $this->getRepository()->findTeams(1, 3, [], ['city' => 1]);
        self::assertEquals(0, $pages);
        self::assertEmpty($teams);
    }
    
    public function testFindTeams_SearchTeamsByName()
    {
        factory(Team::class)->create([
            'id'         => 1,
            'name'       => '社团第一个',
            'creator_id' => 1,
            'city_id'    => 1,
        ]);
        factory(Team::class)->create([
            'id'         => 2,
            'name'       => '社团第二个',
            'creator_id' => 1,
            'city_id'    => 1,
        ]);
        
        list($pages, $teams) = $this->getRepository()->findTeams(1, 3, [], ['city' => 1, 'name' => '第一']);
        
        self::assertEquals(1, $pages);
        self::assertCount(1, $teams);
        self::assertTeam(1, 1, 1, $teams[0]);
    }
    
    //===========================================
    //     findPendingTeamsForCertification
    //===========================================
    public function testFindPendingTeamsForCertification()
    {
        factory(City::class)->create([
            'id'      => 1,
            'name' => '成都',
        ]);
        
        factory(Team::class)->create([
            'city_id'       => 1,
            'certification' => TeamEntity::CERTIFICATION
        ]);
        
        factory(Team::class)->create([
            'city_id'       => 1,
            'certification' => TeamEntity::UN_CERTIFICATION
        ]);
        
        factory(Team::class)->create([
            'city_id'       => 1,
            'certification' => TeamEntity::CERTIFICATION_PENDING
        ]);
        
        factory(Team::class)->create([
            'city_id'       => 1,
            'certification' => TeamEntity::CERTIFICATION_PENDING
        ]);
        
        list($pages, $teams) = $this->getRepository()->findPendingTeamsForCertification(1, 2, ['city']);
    
        self::assertEquals(1, $pages);
        self::assertCount(2, $teams);
    }
    
    //===========================================
    //                exists
    //===========================================
    public function testExists_TeamExists()
    {
        factory(Team::class)->create([
            'id' => 1,
            'city_id' => 1,
        ]);

        self::assertTrue($this->getRepository()->exists(1));
    }

    public function testExists_TeamNotExists()
    {
        self::assertFalse($this->getRepository()->exists(1));
    }
    
    //===========================================
    //                update
    //===========================================
    public function testUpdate_TeamExists()
    {
        factory(Team::class)->create([
                'id' => 1,
                'creator_id' => 1,
                'city_id' => 1,
        ]);
        
        factory(User::class)->create([
                'id' => 1,
        ]);
        
        factory(City::class)->create([
            'id' => 1,
        ]);
        
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator((new UserEntity())->setId(1))
             ->setCity((new CityEntity())->setId(1))
             ->setName('new team name');
    
        self::assertTrue($this->getRepository()->update($team));
    }
    
    public function testUpdate_TeamNotExists()
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator((new UserEntity())->setId(1))
             ->setCity((new CityEntity())->setId(1))
             ->setName('new team name');

        self::assertFalse($this->getRepository()->update($team));
    }
    
    //===========================================
    //              update properties
    //===========================================
    public function testUpdateProperties()
    {
        factory(Team::class)->create([
            'id' => 1,
            'creator_id' => 1,
            'city_id' => 1,
            'status' => TeamEntity::STATUS_NORMAL,
            'tags' => null,
        ]);
    
        factory(User::class)->create([
            'id' => 1,
        ]);
    
        factory(City::class)->create([
            'id' => 1,
        ]);
    
        self::assertTrue($this->getRepository()->updateProperties(1, ['status' => TeamEntity::STATUS_FORBIDDEN]));
        
        $this->seeInDatabase('teams', [
            'id' => 1,
            'creator_id' => 1,
            'city_id' => 1,
            'status' => 1,
            'tags' => null,
        ]);
    }

    //===========================================
    //             update notifiedAt
    //===========================================
    public function testUpdateNotifiedAt()
    {
        $team = factory(Team::class)->create([
             'id' => 1,
             'creator_id' => 1,
             'city_id' => 1,
             'status' => TeamEntity::STATUS_NORMAL,
             'activities_updated_at' => null,
             'members_updated_at' => null,
             'news_updated_at' => null,
             'albums_updated_at' => null,
             'notices_updated_at' => null,
         ]);

        factory(User::class)->create([
             'id' => 1,
         ]);

        factory(City::class)->create([
             'id' => 1,
         ]);

        self::assertTrue($this->getRepository()->updateNotifiedAt(
            1,
            [
                'activities',
                'members',
                'news',
                'albums',
            ]
        ));

        $this->notSeeInDatabase('teams', [
            'id' => 1,
            'activities_updated_at' => null,
        ]);

        $this->notSeeInDatabase('teams', [
            'id' => 1,
            'members_updated_at' => null,
        ]);

        $this->notSeeInDatabase('teams', [
            'id' => 1,
            'news_updated_at' => null,
        ]);

        $this->notSeeInDatabase('teams', [
            'id' => 1,
            'albums_updated_at' => null,
        ]);

        $this->seeInDatabase('teams', [
            'id' => 1,
            'notices_updated_at' => null,
        ]);
    }
    
    //===========================================
    //               add Requirements
    //===========================================
    public function testAddRequirements()
    {
        factory(Team::class)->create([
                'id' => 1,
        ]);
    
        $ret = $this->getRepository()->addRequirements(1, [(new TeamRequirementEntity)->setRequirement('new name')]);
    
        self::assertTrue($ret);
    }
    
    //===========================================
    //               delete Requirements
    //===========================================
    public function testDeleteRequirements_OneRequirementsExists()
    {
        factory(TeamRequirement::class)->create([
            'id' => 1,
        ]);
    
        self::assertTrue($this->getRepository()->deleteRequirements([1]));
    }
    
    public function testDeleteRequirements_MultiRequirementsExists()
    {
        factory(TeamRequirement::class)->create([
            'id' => 1,
        ]);
        
        factory(TeamRequirement::class)->create([
            'id' => 2,
        ]);
        
        factory(TeamRequirement::class)->create([
            'id' => 3,
        ]);
    
        self::assertTrue($this->getRepository()->deleteRequirements([1, 2]));
    }
    
    public function testDeleteRequirements_NoRequirementExists()
    {
        self::assertFalse($this->getRepository()->deleteRequirements([1]));
    }
    
    //===========================================
    //               find Requirements
    //===========================================
    public function testFindRequirements_OneRequirementsExists()
    {
        factory(Team::class)->create([
            'id' => 1,
        ]);
        
        factory(TeamRequirement::class)->create([
            'id' => 1,
            'team_id' => 1,
        ]);
        
        $requirements = $this->getRepository()->findRequirements(1);
        
        self::assertCount(1, $requirements);
    }
    
    public function testFindRequirements_MultiRequirementsExists()
    {
        factory(Team::class)->create([
            'id' => 1,
        ]);
    
        factory(TeamRequirement::class)->create([
            'id' => 1,
            'team_id' => 1,
        ]);
        
        factory(TeamRequirement::class)->create([
            'id' => 2,
            'team_id' => 1,
        ]);
        
        factory(TeamRequirement::class)->create([
            'id' => 3,
            'team_id' => 2,
        ]);
    
        $requirements = $this->getRepository()->findRequirements(1);
    
        self::assertCount(2, $requirements);
    }
    
    public function testFindRequirements_NoRequirementsExists()
    {
        factory(Team::class)->create([
            'id' => 1,
        ]);
    
        self::assertEquals([], $this->getRepository()->findRequirements(1));
    }
    
    //=================================================
    //         updateTeamToPendingCertification
    //=================================================
    public function testUpdateTeamToPendingCertification()
    {
        factory(Team::class)->create([
            'id'            => 1,
            'certification' => TeamEntity::UN_CERTIFICATION,
        ]);
    
        self::assertTrue($this->getRepository()->updateTeamToPendingCertification(1));
    }
    
    public function testUpdateTeamToPendingCertification_CertificationPending()
    {
        factory(Team::class)->create([
            'id'            => 1,
            'certification' => TeamEntity::CERTIFICATION_PENDING,
        ]);

        self::assertFalse($this->getRepository()->updateTeamToPendingCertification(1));
    }
    
    public function testUpdateTeamToPendingCertification_TeamNotExists()
    {
        self::assertFalse($this->getRepository()->updateTeamToPendingCertification(1));
    }
    
    //=================================================
    //           updateTeamToCertification
    //=================================================
    public function testUpdateTeamToCertification()
    {
        factory(Team::class)->create([
            'id'            => 1,
            'certification' => TeamEntity::UN_CERTIFICATION,
        ]);
        
        factory(Team::class)->create([
            'id'            => 2,
            'certification' => TeamEntity::CERTIFICATION_PENDING,
        ]);
    
        self::assertTrue($this->getRepository()->updateTeamToCertification(1));
        self::assertTrue($this->getRepository()->updateTeamToCertification(2));
    }
    
    public function testUpdateTeamToCertification_TeamNotExistsOrHasBeenCertification()
    {
        factory(Team::class)->create([
            'id'            => 1,
            'certification' => TeamEntity::CERTIFICATION,
        ]);
    
        self::assertFalse($this->getRepository()->updateTeamToCertification(1));
        self::assertFalse($this->getRepository()->updateTeamToCertification(2));
    }
    
    //=================================================
    //           updateTeamToUnCertification
    //=================================================
    public function testUpdateTeamToUnCertification()
    {
        factory(Team::class)->create([
            'id'            => 1,
            'certification' => TeamEntity::CERTIFICATION,
        ]);
    
        factory(Team::class)->create([
            'id'            => 2,
            'certification' => TeamEntity::CERTIFICATION_PENDING,
        ]);
    
        self::assertTrue($this->getRepository()->updateTeamToUnCertification(1));
        self::assertTrue($this->getRepository()->updateTeamToUnCertification(2));
    }
    
    public function testUpdateTeamToUnCertification_TeamNotExistsOrHasBeenUnCertification()
    {
        factory(Team::class)->create([
            'id'            => 1,
            'certification' => TeamEntity::UN_CERTIFICATION,
        ]);
    
        self::assertFalse($this->getRepository()->updateTeamToUnCertification(1));
        self::assertFalse($this->getRepository()->updateTeamToUnCertification(2));
    }
    
    //===========================================
    //             add Certifications
    //===========================================
    public function testAddCertifications()
    {
        factory(Team::class)->create([
            'id' => 1,
        ]);
    
        $ret = $this->getRepository()->addCertifications(1, [
                                                            (new TeamCertificationEntity)
                                                                ->setCertificationUrl('http://domain/certification.png')
                                                                ->setType(TeamCertificationEntity::TYPE_BUSSINESS_CERTIFICATES)
                                                            ]);
    
        self::assertTrue($ret);
    }
    
    //===========================================
    //           delete Certifications
    //===========================================
    public function testDeleteCertifications_OneCertificationsExists()
    {
        factory(TeamCertification::class)->create([
            'id' => 1,
        ]);
    
        self::assertTrue($this->getRepository()->deleteCertifications([1]));
    }
    
    public function testDeleteCertifications_MultiCertificationsExists()
    {
        factory(TeamCertification::class)->create([
            'id' => 1,
        ]);
    
        factory(TeamCertification::class)->create([
            'id' => 2,
        ]);
    
        factory(TeamCertification::class)->create([
            'id' => 3,
        ]);
    
        self::assertTrue($this->getRepository()->deleteCertifications([1, 2]));
    }
    
    public function testDeleteCertifications_NoCertificationExists()
    {
        self::assertFalse($this->getRepository()->deleteCertifications([1]));
    }
    
    //===========================================
    //            find Certifications
    //===========================================
    public function testFindCertifications_OneCertificationsExists()
    {
        factory(Team::class)->create([
            'id' => 1,
        ]);
    
        factory(TeamCertification::class)->create([
            'id' => 1,
            'team_id' => 1,
        ]);
    
        $certifications = $this->getRepository()->findCertifications(1);
    
        self::assertCount(1, $certifications);
    }
    
    public function testFindCertifications_MultiCertificationsExists()
    {
        factory(Team::class)->create([
            'id' => 1,
        ]);
    
        factory(TeamCertification::class)->create([
            'id' => 1,
            'team_id' => 1,
        ]);
    
        factory(TeamCertification::class)->create([
            'id' => 2,
            'team_id' => 1,
        ]);
    
        factory(TeamCertification::class)->create([
            'id' => 3,
            'team_id' => 2,
        ]);
    
        $certifications = $this->getRepository()->findCertifications(1);
    
        self::assertCount(2, $certifications);
    }
    
    public function testFindCertifications_NoCertificationsExists()
    {
        factory(Team::class)->create([
            'id' => 1,
        ]);
    
        self::assertEquals([], $this->getRepository()->findCertifications(1));
    }

    //===========================================
    //              findTeamsOf
    //===========================================
    public function testFindTeamsOf()
    {
        for ($i = 0; $i < 5; $i++) {
            factory(Team::class)->create([
                'id'                    => $i + 1,
                'creator_id'            => 1,
                'city_id'               => 1,
                'activities_updated_at' => date('Y-m-d H:i:s', strtotime("{$i}0 seconds")),
            ]);
        }

        list($total, $teams) = $this->getRepository()->findTeamsOf([2, 3, 4]);

        self::assertEquals(3, $total);
        self::assertCount(3, $teams);
        for ($i = 0; $i < 3; $i++) {
            self::assertTeam(4 - $i, 1, 1, $teams[$i]);
        }
    }

    /**
     * @return \Jihe\Contracts\Repositories\TeamRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\TeamRepository::class];
    }
}
