<?php
namespace Jihe\Services;

use Jihe\Contracts\Repositories\UserTagRepository;

/**
 * User tag related service
 */
class TagService
{
    /**
     * repository for user tag
     *
     * @var \Jihe\Contracts\Repositories\UserTagRepository
     */
    private $userTagRepository;

    public function __construct(UserTagRepository $userTagRepository)
    {
        $this->userTagRepository = $userTagRepository;
    }

    /**
     * list all user tags
     *
     * return array element is \Jihe\Entities\UserTag
     */
    public function listTags()
    {
        return $this->userTagRepository->findAll(); 
    }
}
