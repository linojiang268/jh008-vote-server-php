<?php

namespace Jihe\Exceptions;

/**
 * global exception code definitions
 *
 */
final class ExceptionCode 
{
    /**
     * Code for general exception. When a general exception is received, nothing more
     * can be done but show the error message.
     * 
     * @var int
     */
    const GENERAL = 10000;

    /**
     *  throws when verification code is incorrect.
     *  
     * @var int
     */
    const INCORRECT_VERIFICATION = 10001;

    /**
     * throws when request params signature is incorrect.
     *
     * @var int
     */
    const INCORRECT_REQUEST_SIGN = 10002;

    //========================================
    //                User
    //========================================

    /**
     * Code for unauthorized user exception. When an authorized user is required but that's not
     * true, this exception will be thrown.
     *
     * @var int
     */
    const USER_UNAUTHORIZED = 10101;

    /**
     * To access user-related API, user should have his/her information, such as gender, tags, 
     * be populated. If not, user will be requested to do that.
     * 
     * @var int
     */
    const USER_INFO_INCOMPLETE = 10102;

    /**
     * The same user only login on one device at the same time, if
     * account login on another device, account on current device
     * will be kicked
     *
     * @var int
     */
    const USER_KICKED = 10103;

    //========================================
    //                News
    //========================================

    /**
     * the news edited, deleted or getted is not exists.
     *
     * @var int
     */
    const NEWS_NOT_EXIST = 10201;

    //========================================
    //                Activity
    //========================================
    
    const ACTIVITY_NONE_GROUP_INFO = 10301;

    /**
     * user is not member of activity.
     *
     * @var int
     */
    const USER_NOT_ACTIVITY_MEMBER = 10302;

    //========================================
    //                Team
    //========================================

    /**
     * user is not member of team.
     *
     * @var int
     */
    const USER_NOT_TEAM_MEMBER = 10401;
}
