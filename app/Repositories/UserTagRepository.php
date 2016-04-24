<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\UserTagRepository as UserTagRepositoryContract;
use Jihe\Models\UserTag;
use Jihe\Entities\UserTag as UserTagEntity;;

class UserTagRepository implements UserTagRepositoryContract
{
    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\UserTagRepository::findAll()
     */
    public function findAll()
    {
        return array_map([ $this, 'convertToEntity' ], UserTag::all()->all());
    }

    /**
     * @param \Jihe\Models\UserTag|null $userTag
     *
     * @return \Jihe\Entities\UserTag|null
     */
    private function convertToEntity($userTag)
    {
        return $userTag ? $userTag->toEntity() : null;
    }
}
