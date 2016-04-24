<?php
namespace Jihe\Contracts\Repositories;

use Jihe\Entities\UserTag;

interface UserTagRepository
{
    /**
     * fetch all user tags
     *
     * @return array    element is \Jihe\Entities\UserTag
     */
    public function findAll();
}
