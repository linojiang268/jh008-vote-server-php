<?php

namespace Jihe\Services;

use Jihe\Contracts\Repositories\UserAttributeRepository;

class UserAttributeService {

    private $userAttributeRepository;

    public function __construct(UserAttributeRepository $userAttributeRepository) {
        $this->userAttributeRepository = $userAttributeRepository;
    }

    public function getUserAttrValue($mobile, $attrName) {
        return $this->userAttributeRepository->get($mobile, $attrName, null);
    }

    public function setUserAttr($mobile, $attrName, $attrValue) {
        return $this->userAttributeRepository->addOrUpdate($mobile, $attrName, $attrValue);
    }

    public function deleteUserAttr($mobile, $attrName) {
        return $this->userAttributeRepository->delete($mobile, $attrName);
    }

}
