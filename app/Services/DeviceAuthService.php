<?php
namespace Jihe\Services;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Jihe\Contracts\Repositories\LoginDeviceRepository;
use Jihe\Entities\LoginDevice;
use Jihe\Entities\Message;
use Jihe\Utils\StringUtil;
use Jihe\Utils\PushTemplate;
use Jihe\Dispatches\DispatchesPushMessage;

class DeviceAuthService
{
    use DispatchesJobs;
    use DispatchesPushMessage;

    private $cookieJar;
    private $loginDeviceRepository;
    private $request;

    public function __construct(
        CookieJar $cookieJar,
        Request $request,
        LoginDeviceRepository $loginDeviceRepository
    ) {
        $this->cookieJar = $cookieJar;
        $this->request = $request;
        $this->loginDeviceRepository = $loginDeviceRepository;
    }

    /**
     * attach device to user after login
     *
     * @param string $mobile    user mobile
     * 
     * @return string|null      user device identifier if source valid
     */
    public function attachDeviceAfterLogin($mobile)
    {
        $source = $this->getSourceFromRequest();
        if ( ! $source) {
            return;
        }

        // Get identifier from cookie, or generate if it not exists
        $identifier = $this->getIdentifierOrGenerate($mobile);
        $this->loginDeviceRepository
            ->addOrUpdateIdentifierIfExists($mobile, $source, $identifier);

        // queued device cookie
        $this->setIdentifierCookie($identifier);

        return $identifier;
    }

    /**
     * Check identifier and old identifier, if they are not equal, it means that
     * we should notify user, who owned the old identifier, areay be kicked
     *
     * we put this action after login, because we should wait client finished
     * push alias (identifier) binding
     *
     * @param integer $mobile     user mobile
     *
     * @return void
     */
    public function checkDeviceAndKickUserOnOtherDevice($mobile)
    {
        $source = $this->getSourceFromRequest();
        if ( ! $source) {
            return;
        }

        $loginDevice = $this->loginDeviceRepository
            ->findOneByMobileAndSource($mobile, $source);

        if ($loginDevice->getOldIdentifier() &&
            $loginDevice->getIdentifier() != $loginDevice->getOldIdentifier() &&
            $source == LoginDevice::SOURCE_CLIENT
        ) {
            $this->noticeKickedUser($loginDevice->getOldIdentifier());
        }
    }

    public function check($mobile)
    {
        // only valid source should be check
        $source = $this->getSourceFromRequest();
        if ( ! $source) {
            return true;
        }

        $identifier = $this->getIdentifierFromRequest();
        if ( ! $identifier) {
            return false;
        }

        $loginDevice = $this->loginDeviceRepository
            ->findOneByMobileAndSource($mobile, $source);
        if ( ! $loginDevice || ! $loginDevice->checkIdentifier($identifier)) {
            return false;
        }

        return true;
    }

    /**
     * Clearup device related data after user logout
     *
     * @param string $mobile    user mobile number
     */
    public function clearupAfterUserLogout($mobile)
    {
        $this->removeIdentifierCookie($mobile);
    }

    /**
     * Get login source from request
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return integer|null
     */
    private function getSourceFromRequest()
    {
        $maps = [
            'api'       => LoginDevice::SOURCE_CLIENT,
            'community' => LoginDevice::SOURCE_BACKSTAGE,
        ];
        $prefix = $this->request->segment(1);
        return array_key_exists($prefix, $maps) ? $maps[$prefix] : null;
    }

    /**
     * Get identifier from cookie, or generate a new identifier if which not exists
     * in cookie
     *
     * @param string $mobile
     *
     * @return string
     */
    private function getIdentifierOrGenerate($mobile)
    {
        if ($identifier = $this->getIdentifierFromRequest()) {
            return $identifier;
        }

        return $this->generateIdentifier($mobile);
    }

    /**
     * Get identifier from request
     *
     * @return string
     */
    private function getIdentifierFromRequest()
    {
        return $this->request->cookie($this->getCookieName());
    }

    /**
     * Set identifier in cookie, and put cookie into cookie queue, middleware
     * AddQueuedCookiesToResponse will set all queued cookie into response
     *
     * @param string $identifier
     *
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    private function setIdentifierCookie($identifier)
    {
        $cookie = $this->cookieJar->forever($this->getCookieName(), $identifier);
        $this->cookieJar->queue($cookie);
    }

    private function removeIdentifierCookie($mobile)
    {
        // We cannot notify app remove a specified cookie, so the only thing
        // we can do is change the cookie value to a new identifier, in order
        // to make sure old identifer not be used in next login request
        $identifier = $this->generateIdentifier($mobile);
        $this->cookieJar->queue(
            $this->cookieJar->forever($this->getCookieName(), $identifier)
        );
    }

    private function getCookieName()
    {
        return 'jihe_deviceno';
    }

    /**
     * Generate a unique device identifier
     *
     * @param string $mobile
     *
     * @return string   48 bit string
     */
    private function generateIdentifier($mobile)
    {
        $signStr = md5($mobile . time() . StringUtil::quickRandom());

        return $mobile . '_' . $signStr . StringUtil::quickRandom(4);
    }

    private function noticeKickedUser($pushAlias)
    {
        $this->dispatchPushToUserMessage(
            $pushAlias,
            [
                'type'          => Message::TYPE_KICK,
                'content'       => PushTemplate::generalMessage(PushTemplate::USER_KICKED),
            ]);
    }
}
