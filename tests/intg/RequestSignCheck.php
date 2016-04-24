<?php
namespace intg\Jihe;

trait RequestSignCheck
{
    /**
     * @before
     */
    public function disableRequestSignMiddleware()
    {
        $this->app->instance('middleware.request.sign.disable', true);

        return $this;
    }


    public function enableRequestSignMiddleware()
    {
        $this->app->instance('middleware.request.sign.disable', false);

        return $this;
    }
    
}
