<?php

namespace Jihe\Contracts\Repositories;

interface UserAttributeRepository {

    /**
     * @param string $mobile
     * @param string $attrName attr key name
     * @param string $defaultValue attr key default value
     * @return string attr value 
     */
    public function get($mobile, $attrName, $defaultValue);

    /**
     * @param string $mobile
     * @param string $attrName attr key name
     * @param string $attrValue attr key value
     * @return true|false
     */
    public function addOrUpdate($mobile, $attrName, $attrValue);

    /**
     * @param string $mobile
     * @param string $attrName attr key name
     * @return true|false
     */
    public function delete($mobile, $attrName);
}
