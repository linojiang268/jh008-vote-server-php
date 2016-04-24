<?php
namespace intg\Jihe;

trait AuthDeviceCheck
{
    /**
     * @before
     */
    public function disableAuthDeviceMiddleware()
    {
        $this->app->instance('middleware.auth.device.disable', true);

        return $this;
    }


    public function enableAuthDeviceMiddleware()
    {
        $this->app->instance('middleware.auth.device.disable', false);

        return $this;
    }
}
