<?php
namespace Jihe\Entities;

use Jihe\Entities\Team;

class TeamCertification
{
    const TYPE_ID_CARD_FRONT = 0;// 身份证正面
    const TYPE_ID_CARD_BACK = 1;// 身份证反面
    const TYPE_BUSSINESS_CERTIFICATES = 2;// 营业相关证件
    
    private $id;
    private $certificationUrl;
    private $type;
    
    private $team;
    
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getCertificationUrl()
    {
        return $this->certificationUrl;
    }

    public function setCertificationUrl($certificationUrl)
    {
        $this->certificationUrl = $certificationUrl;
        return $this;
    }

    /**
     * @return \Jihe\Entities\Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param \Jihe\Entites\Team $team
     * @return \Jihe\Entities\TeamCertification
     */
    public function setTeam(Team $team)
    {
        $this->team = $team;
        return $this;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
