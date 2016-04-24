<?php

namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\UserAttributeRepository as UserAttributeRepositoryContract;
use Jihe\Models\UserAttribute;

class UserAttributeRepository implements UserAttributeRepositoryContract {

    public function addOrUpdate($mobile, $attrName, $attrValue) {
        $oldValue = $this->get($mobile, $attrName, null);
        if ($oldValue == null) {
            return null != UserAttribute::create([
                        'mobile' => $mobile,
                        'attr_name' => $attrName,
                        'attr_value' => $attrValue,
            ]);
        }

        return UserAttribute::where('mobile', $mobile)->where('attr_name', $attrName)->update([
                    'attr_value' => $attrValue,
        ]);
    }

    public function delete($mobile, $attrName) {
        return UserAttribute::where('mobile', $mobile)->where('attr_name', $attrName)->delete();
    }

    public function get($mobile, $attrName, $defaultValue) {
        $attr = UserAttribute::where('mobile', $mobile)->where('attr_name', $attrName)->orderBy('id', 'DESC')->first();
        if ($attr) {
            return $attr->value('attr_value');
        }
        return $defaultValue;
    }

}
