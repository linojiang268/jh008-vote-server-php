<?php

Route::group(['prefix' => 'wap'], function () {
    Route::get('news/detail', '\Jihe\Http\Controllers\Backstage\NewsController@detail');// news_id={$newsId}
    Route::get('team/detail', '\Jihe\Http\Controllers\Backstage\TeamController@detail');// team_id={$teamId}
    Route::get('activity/detail', ['as' => 'activity.detail', 'uses' => '\Jihe\Http\Controllers\Backstage\ActivityController@detail']); //activity_id={$activityId}
    Route::get('activity/userStatus', '\Jihe\Http\Controllers\Backstage\ActivityController@wapActivityUserSignUpStatus'); //activity_id={$activityId}&mobile=xxxx
    Route::get('activity/pay', '\Jihe\Http\Controllers\Backstage\ActivityController@wapActivityPay'); //activity_id={$activityId}
    Route::get('download', '\Jihe\Http\Controllers\Backstage\TeamController@download');

    Route::get('checkin/detail', '\Jihe\Http\Controllers\Backstage\ActivityController@wapActivityCheckin'); //activity_id={$activityId}&step={$step}&ver=1
    //Route::get('activity/checkin', '\Jihe\Http\Controllers\Backstage\ActivityController@wapActivityCheckin'); //activity_id={$activityId}&step={$step}&ver=1
    Route::get('activity/failed', '\Jihe\Http\Controllers\Backstage\ActivityController@wapSignFailed');
    Route::get('activity/info', '\Jihe\Http\Controllers\Backstage\ActivityController@wapActivityInfo');
    Route::get('wechat/oauth/go', 'Api\WechatController@goToOauth');
    Route::get('wechat/oauth', 'Api\WechatController@doOauth');
    Route::get('activity/applicant/search', 'Api\ActivityApplicantController@searchActivityApplicantFromWeb');

    // routes for wap of attendant
    Route::group(['prefix' => 'attendant'], function () {
        Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\AttendantController@getAttendants');
        Route::get('detail', '\Jihe\Http\Controllers\Backstage\AttendantController@detail');
        Route::post('enroll', '\Jihe\Http\Controllers\Backstage\AttendantController@enroll');
        Route::post('vote', '\Jihe\Http\Controllers\Backstage\AttendantController@vote');
        Route::get('cookie/valid', '\Jihe\Http\Controllers\Backstage\AttendantController@validCookie');
        Route::get('/', '\Jihe\Http\Controllers\Backstage\AttendantController@attendant');
        Route::post('image/tmp/upload', '\Jihe\Http\Controllers\Backstage\ImageController@tmpUpload');
    });

    // routes for wap of plane model
    Route::group(['prefix' => 'planemodel'], function () {
        Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\PlaneModelController@getAttendants');
        Route::get('detail', '\Jihe\Http\Controllers\Backstage\PlaneModelController@detail');
        Route::post('enroll', '\Jihe\Http\Controllers\Backstage\PlaneModelController@enroll');
        Route::post('vote', '\Jihe\Http\Controllers\Backstage\PlaneModelController@vote');
        Route::get('/', '\Jihe\Http\Controllers\Backstage\PlaneModelController@attendant');
        Route::post('image/tmp/upload', '\Jihe\Http\Controllers\Backstage\ImageController@tmpUpload');
    });

    // routes for wap of shiyang
    Route::group(['prefix' => 'shiyang'], function () {
        Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\ShiyangAttendantController@getAttendants');
        Route::get('detail', '\Jihe\Http\Controllers\Backstage\ShiyangAttendantController@detail');
        Route::post('enroll', '\Jihe\Http\Controllers\Backstage\ShiyangAttendantController@enroll');
        Route::post('vote', '\Jihe\Http\Controllers\Backstage\ShiyangAttendantController@vote');
        Route::get('/', '\Jihe\Http\Controllers\Backstage\ShiyangAttendantController@attendant');
        Route::post('image/tmp/upload', '\Jihe\Http\Controllers\Backstage\ImageController@tmpUpload');
    });

    // routes for wap of gaoxin
    Route::group(['prefix' => 'gaoxin'], function () {
        Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@getAttendants');
        Route::get('approved/sort/list', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@getSortAttendants');
        Route::get('detail', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@detail');
        Route::post('enroll', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@enroll');
        Route::post('vote', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@vote');
        Route::get('/', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@attendant');
        Route::post('image/tmp/upload', '\Jihe\Http\Controllers\Backstage\ImageController@tmpUpload');
    });

    Route::group(['prefix' => 'xinnian'], function () {
        Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@getAttendants');
        Route::get('approved/sort/list', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@getSortAttendants');
        Route::get('detail', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@detail');
        Route::post('search', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@search');
        Route::post('enroll', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@enroll');
        Route::post('vote', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@vote');
        Route::get('/', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@attendant');
        Route::post('image/tmp/upload', '\Jihe\Http\Controllers\Backstage\ImageController@tmpUpload');
    });

    Route::get('team/create', '\Jihe\Http\Controllers\Backstage\TeamController@profileByWap');

});

/*
  |------------------------------------------------------------------------
  | Routes for backstage of attendant
  |------------------------------------------------------------------------
  |
 */
Route::group(['prefix' => 'attendant'], function () {
    Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\AttendantController@listApprovedAttendants');
    Route::get('pending/list', '\Jihe\Http\Controllers\Backstage\AttendantController@listPendingAttendants');
    Route::post('approve', '\Jihe\Http\Controllers\Backstage\AttendantController@approve');
    Route::post('remove', '\Jihe\Http\Controllers\Backstage\AttendantController@remove');
});

Route::group(['prefix' => 'planemodel'], function () {
    Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\PlaneModelController@listApprovedAttendants');
    Route::get('pending/list', '\Jihe\Http\Controllers\Backstage\PlaneModelController@listPendingAttendants');
    Route::post('approve', '\Jihe\Http\Controllers\Backstage\PlaneModelController@approve');
    Route::post('remove', '\Jihe\Http\Controllers\Backstage\PlaneModelController@remove');
});

Route::group(['prefix' => 'shiyang'], function () {
    Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\ShiyangAttendantController@listApprovedAttendants');
    Route::get('pending/list', '\Jihe\Http\Controllers\Backstage\ShiyangAttendantController@listPendingAttendants');
    Route::post('approve', '\Jihe\Http\Controllers\Backstage\ShiyangAttendantController@approve');
    Route::post('remove', '\Jihe\Http\Controllers\Backstage\ShiyangAttendantController@remove');
});

Route::group(['prefix' => 'gaoxin'], function () {
    Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@listApprovedAttendants');
    Route::get('pending/list', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@listPendingAttendants');
    Route::post('approve', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@approve');
    Route::post('remove', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@removeApplicants');
    Route::post('attendant/remove', '\Jihe\Http\Controllers\Backstage\GaoxinAttendantController@removeAttendants');
});


Route::group(['prefix' => 'xinnian'], function () {
    Route::get('approved/list', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@listApprovedAttendants');
    Route::get('pending/list', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@listPendingAttendants');
    Route::post('approve', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@approve');
    Route::post('remove', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@removeApplicants');
    Route::post('attendant/remove', '\Jihe\Http\Controllers\Backstage\XinNianAttendantController@removeAttendants');
});

/*
  |------------------------------------------------------------------------
  | Routes for team-manager
  |------------------------------------------------------------------------
  |
 */
Route::group(['namespace' => 'Backstage', 'prefix' => 'community', 'middleware' => 'csrf'], function () {
    Route::group([], function () {
        Route::get('/', 'HomeController@login');
        Route::get('/complete', 'HomeController@complete');

        Route::post('login', '\Jihe\Http\Controllers\Api\AuthController@login');
        Route::get('logout', '\Jihe\Http\Controllers\Api\AuthController@logout');
        Route::post('image/tmp/upload', 'ImageController@tmpUpload');
        Route::post('image/ueditor/upload', 'ImageController@uploadFromUeditor');

        Route::get('web/activity/applicant/verifycode', 'ActivityApplicantController@sendVerifyCodeForWebApplicant');
        Route::post('web/activity/applicant', 'ActivityApplicantController@applicantFromWeb');
    });

    Route::group(['prefix' => 'news', 'middleware' => ['auth', 'auth.device', 'team.inject']], function () {
        Route::get('/', 'NewsController@getTeamNews');
        Route::post('/', 'NewsController@publishNews');
        Route::get('/{newsId}', 'NewsController@getNews')->where('newsId', '[0-9]+');
        Route::get('/{newsId}/update', 'NewsController@getNewsForUpdate')->where('newsId', '[0-9]+');
        Route::put('/{newsId}', 'NewsController@updateNews')->where('newsId', '[0-9]+');
        Route::delete('/{newsId}', 'NewsController@deleteNews')->where('newsId', '[0-9]+');
    });

    Route::group(['prefix' => 'team', 'middleware' => ['auth', 'auth.device']], function () {
        Route::get('setting/profile', 'TeamController@profile');
        Route::group(['middleware' => 'team.inject'], function () {
            Route::get('setting/authentication', 'TeamController@authentication');
            Route::get('setting/condition', 'TeamController@condition');
            Route::get('setting/passwd', 'TeamController@passwd');
            Route::get('setting/bind', 'TeamController@bind');

            Route::get('verify/pend', 'TeamController@verifyPend');
            Route::get('verify/refuse', 'TeamController@verifyRefuse');
            Route::get('verify/blacklist', 'TeamController@verifyBlacklist');
            Route::get('verify/whitelist', 'TeamController@verifyWhitelist');


            Route::post('update/certifications', 'TeamController@requestCertifications');
            Route::get('manager', 'TeamController@manager');
            Route::get('manager/group', 'TeamController@managerGroup');
            Route::get('notice', 'TeamController@notice');
            Route::get('notice/list', 'TeamController@noticeList');
            Route::get('statistics', 'TeamController@statistics');
            Route::get('qrcode', 'TeamController@qrcode');
        });

        Route::post('request/create', 'TeamController@requestForEnrollment');
        Route::post('request/inspect', 'TeamController@inspectRequest');

        Route::group(['middleware' => 'team.inject'], function () {
            Route::post('request/update', 'TeamController@requestForUpdate');
            Route::post('update', 'TeamController@update');
            Route::post('requirement/update', 'TeamController@updateRequirements');
            Route::post('certification/request', 'TeamController@requestCertifications');
            Route::get('qrcode/download', 'TeamController@downloadQrcode');
            Route::post('notice/send', 'TeamController@sendNotice');
            Route::get('notices/list', 'TeamController@listNotices');
            // test verify team
            Route::get('verify/create', 'TeamController@verifyForEnrollment');
            Route::get('verify/update', 'TeamController@verifyForUpdate');

            Route::group(['prefix' => 'member'], function () {
                Route::post('group/update', 'TeamMemberController@changeMemberGroup');
                Route::get('list', 'TeamMemberController@listMembers');
                Route::post('update', 'TeamMemberController@update');
                Route::get('export', 'TeamMemberController@exportMembers');
                Route::post('enrollment/reject', 'TeamMemberController@rejectEnrollmentRequest');
                Route::post('enrollments/reject', 'TeamMemberController@rejectEnrollmentRequests');
                Route::post('enrollment/approve', 'TeamMemberController@approveEnrollmentRequest');
                Route::post('enrollment/whitelist/import', 'TeamMemberController@importEnrollmentWhitelist');
                Route::post('enrollment/update', 'TeamMemberController@updateEnrollmentRequest');
                Route::post('enrollment/whitelist/update', 'TeamMemberController@updateEnrollmentPermission');
                Route::post('enrollment/blacklist/update', 'TeamMemberController@updateEnrollmentPermission');
                Route::delete('enrollment/whitelist', 'TeamMemberController@deleteEnrollmentPermission');
                Route::post('enrollment/whitelist', 'TeamMemberController@addEnrollmentWhitelist');
                Route::get('enrollment/whitelist', 'TeamMemberController@showEnrollmentWhitelist');
                Route::post('enrollment/blacklist', 'TeamMemberController@blacklistEnrollmentRequest');
                Route::delete('enrollment/blacklist', 'TeamMemberController@whiteBlacklistedEnrollmentRequest');
                Route::get('enrollment/blacklist', 'TeamMemberController@showEnrollmentBlacklist');
                Route::get('enrollment/pending', 'TeamMemberController@listPendingEnrollmentRequestForTeam');
                Route::get('enrollment/rejected', 'TeamMemberController@listRejectedEnrollmentRequestForTeam');
            });

            Route::group(['prefix' => 'group'], function () {
                Route::post('create', 'TeamGroupController@createGroup');
                Route::post('update', 'TeamGroupController@updateGroup');
                Route::post('delete', 'TeamGroupController@deleteGroup');
                Route::get('list', 'TeamGroupController@listGroups');
            });
        });
    });

    Route::group(['prefix' => 'activity', 'middleware' => ['auth', 'auth.device', 'team.inject']], function () {
        Route::get('publish', 'ActivityController@publish');
        Route::get('publish/{id}', 'ActivityController@publish');
        Route::get('list', 'ActivityController@actList');
        Route::get('news/publish', 'ActivityController@newsPublish');
        Route::get('news/publish/{id}', 'ActivityController@newsPublish');
        Route::get('news/list', 'ActivityController@newsList');
        //Route::get('{id}/manage/share', 'ActivityController@manageShare');
        //Route::get('{id}/manage/sign', 'ActivityController@manageSign');
        //Route::get('{id}/manage/check', 'ActivityController@manageCheck');
        //Route::get('{id}/manage/group', 'ActivityController@manageGroup');
        //Route::get('{id}/manage/inform', 'ActivityController@manageInform');
        //Route::get('{id}/manage/photo/master', 'ActivityController@managePhotoMaster');
        //Route::get('{id}/manage/photo/users', 'ActivityController@managePhotoUsers');
        //Route::get('manage/photo/{id}', 'ActivityController@managePhotoSingle');

        Route::post('add', 'ActivityController@create');
        Route::get('{id}/manage/qrcode', 'ActivityController@managerQrcode'); 
        Route::get('{id}/manage', 'ActivityController@manage');
        Route::get('{id}/manage/share', 'ActivityController@manageShare'); // ����
        Route::get('{id}/manage/sign', 'ActivityController@manageSign');  // �����
        Route::get('{id}/manage/check', 'ActivityController@manageCheck'); // �������
        Route::get('{id}/manage/line', 'ActivityController@manageLine'); // ·��ָ��
        //Route::get('{id}/manage/group', 'ActivityController@manageGroup');
        Route::get('{id}/manage/inform', 'ActivityController@manageInform');
        Route::get('{id}/manage/photo/master', 'ActivityController@managePhotoMaster'); // ���췽���
        Route::get('{id}/manage/photo/users', 'ActivityController@managePhotoUsers'); // �û����
        //Route::get('manage/photo/{id}', 'ActivityController@managePhotoSingle');
        Route::get('{id}/manage/photo/files', 'ActivityController@manageFiles'); // �ĵ�����

        Route::post('create', 'ActivityController@create');
        Route::post('update', 'ActivityController@update');
        Route::post('dopublish', 'ActivityController@doPublish');
        Route::get('listdata', 'ActivityController@getActivitiesByTeam');
        Route::get('search/name', 'ActivityController@getTeamActivitiesByName');
        Route::get('info', 'ActivityController@getActivityById');
        Route::post('delete', 'ActivityController@deleteActivityById');
        Route::post('search/time', 'ActivityController@searchTeamActivitiesByActivityTime');
        Route::post('send/notice', 'ActivityController@sendNotice');
        Route::get('notice/list', 'ActivityController@listNotices');
        Route::get('import/members/template', 'ActivityController@importActivityMembersTemplate');
        Route::post('import/members', 'ActivityController@importActivityMembers');
        Route::post('member/add', 'ActivityApplicantController@addSingleVip');
        Route::get('member/export', 'ActivityMemberController@exportMembers');

        Route::post('/plan/add', 'ActivityController@createActivityPlans');
        Route::get('/plan/list', 'ActivityController@getActivityPlans');

        Route::post('group/create', 'ActivityGroupController@createGroups');
        Route::get('group/list', 'ActivityGroupController@getGroups');
        Route::post('group/delete', 'ActivityGroupController@deleteGroup');
        Route::post('member/group/update', 'ActivityMemberController@setGroup');

        Route::post('checkin/qrcode/create', 'ActivityCheckInController@createCheckInQRCodes');
        Route::get('checkin/qrcode/list', 'ActivityCheckInController@getCheckInQRCodes');
        Route::get('checkin/qrcode/download', 'ActivityCheckInController@downloadCheckInQRCodes');
        Route::get('checkin/list/all', 'ActivityCheckInController@listActivityCheckIn');

        Route::get('applicant/list', 'ActivityApplicantController@getApplicantsList');
        Route::post('applicant/approve', 'ActivityApplicantController@approveApplicant');
        Route::post('applicant/refuse', 'ActivityApplicantController@refuseApplicant');
        Route::post('applicant/payment/enforce', 'ActivityApplicantController@enforcePayment');

        Route::group(['prefix' => 'album/image'], function () {
            Route::get('list', 'ActivityController@listAlbumImages');
            Route::post('add', 'ActivityController@addAlbumImage');
            Route::post('approve', 'ActivityController@approveAlbumImages');
            Route::post('remove', 'ActivityController@removeAlbumImages');
        });
        Route::get('file/list', 'ActivityController@listFiles');
        Route::post('file/add', 'ActivityController@addFile');
        Route::post('file/remove', 'ActivityController@removeFiles');
    });

    Route::group(['prefix' => 'finance', 'middleware' => ['auth', 'auth.device', 'team.inject']], function () {
        Route::get('index', 'FinanceController@index');
        Route::get('index/{activity}', 'FinanceController@indexDetail');
        Route::get('stream', 'FinanceController@stream');
        Route::get('withdrawals', 'FinanceController@withdrawals');
        Route::get('withdrawals/list', 'FinanceController@withdrawalsList');
        Route::get('refund', 'FinanceController@refund');
        Route::get('enrollment/income', 'FinanceController@listActivityEnrollIncomeForTeam');
        Route::get('enrollment/payment', 'FinanceController@listPaymentsForActivity');
    });
});

/*
  |------------------------------------------------------------------------
  | Routes for ueditor
  |------------------------------------------------------------------------
  |
 */
Route::group(['namespace' => 'Backstage', 'prefix' => 'community', 'middleware' => ['auth', 'auth.device']], function () {
    Route::get('/ueditor', 'UEditorFileController@upload');
    Route::post('/ueditor', 'UEditorFileController@upload');
});

/*
  |------------------------------------------------------------------------
  | Routes for api
  |------------------------------------------------------------------------
  |
 */
Route::post('api/activity/checkin/quick', 'Api\ActivityCheckInController@firstStepQuickCheckIn');
Route::group(['prefix' => 'api', 'namespace' => 'Api', 'middleware' => 'request.sign'], function () {
    Route::get('register/verifycode', 'AuthController@sendVerifyCodeForRegistration');
    Route::post('register', 'AuthController@register');
    Route::post('login', 'AuthController@login');
    Route::get('password/reset/verifycode', 'AuthController@sendVerifyCodeForPasswordReset');
    Route::get('password/reset', 'AuthController@resetPasswordForm');
    Route::group(['middleware' => 'csrf'], function () {
        Route::post('password/reset', 'AuthController@resetPassword');
    });
    Route::get('city/list', 'CityController@getCities');
    Route::get('activity/checkin/verifycode', 'ActivityCheckInController@sendVerifyCodeForCheckIn');

    Route::group(['middleware' => ['auth', 'auth.device']], function () {
        Route::get('alias/bound', 'AuthController@pushAliasBound');
        Route::get('logout', 'AuthController@logout');
        Route::post('password/change', 'AuthController@changePassword');

        Route::group(['prefix' => 'user'], function () {
            Route::get('profile', 'UserController@showProfile');
            Route::post('profile', 'UserController@updateProfile');
            Route::post('profile/complete', 'UserController@completeProfile');
            Route::get('identity/reset', 'UserController@resetIdentity');
        });

        Route::group(['prefix' => 'team'], function () {
            Route::post('member/enroll', 'TeamMemberController@requestForEnrollment');
            Route::post('member/quit', 'TeamMemberController@quitTeam');
            Route::post('member/update', 'TeamMemberController@update');
            Route::get('member/list', 'TeamMemberController@listMembers');
            Route::get('enrollment/pending', 'TeamMemberController@listPendingEnrollmentRequestForUser');
            Route::get('list', 'TeamController@getTeams');
            Route::get('relate/list', 'TeamController@getRelateTeams');
            Route::get('self/list', 'TeamController@getTeamsOfUser');
            Route::get('info', 'TeamController@getTeam');
        });

        Route::group(['prefix' => 'activity'], function () {
            Route::get('city/list', 'ActivityController@listActivitiesInCity');
            Route::get('team/list', 'ActivityController@listActivitiesByTeam');
            Route::get('search/name', 'ActivityController@searchActivitiesInCity');
            Route::get('info', 'ActivityController@getDetail');
            Route::get('detail', 'ActivityController@getNewDetail');
            Route::get('search/point', 'ActivityController@listActivitiesByPoint');
            Route::get('hasalbum/list', 'ActivityController@listActivitiesByHasAlbum');
            Route::get('no/score', 'ActivityController@getCurrentUserNoScoreActivities');
            Route::get('my', 'ActivityController@getMyActivities');
            Route::get('my/count', 'ActivityController@getMyActivitiesCount');
            Route::get('my/topics', 'ActivityController@getMyTopics');
            Route::get('recommend', 'ActivityController@recommendActivities');
            Route::get('checkin/info', 'ActivityController@getCheckInActivityDetail');
            Route::get('pay/timeout/seconds', 'ActivityController@getPaymentTimeoutSeconds');
            Route::get('home/my', 'ActivityController@getHomePageMyActivities');

            Route::post('checkIn/checkIn', 'ActivityCheckInController@checkIn');
            Route::get('checkIn/list', 'ActivityCheckInController@getCheckInList');
            Route::get('member/list', 'ActivityMemberController@getActivityMembers');
            Route::get('member/location', 'ActivityMemberController@getGroupMemberLocation');
            Route::post('member/score', 'ActivityMemberController@score');
            Route::post('applicant/applicant', 'ActivityApplicantController@applicantActivity');
            Route::get('applicant/payment/info', 'ActivityApplicantController@getApplicantInfoForPayment');

            Route::group(['prefix' => 'album/image'], function () {
                Route::get('list', 'ActivityController@listApprovedAlbumImages');
                Route::post('add', 'ActivityController@addAlbumImage');
                Route::post('self/remove', 'ActivityController@removeAlbumImagesOfUser');
                Route::get('self/list', 'ActivityController@listAlbumImagesOfUser');
            });
            Route::get('file/list', 'ActivityController@listFiles');
        });
        Route::get('news', 'NewsController@getTeamNews');
        Route::get('/news/{newsId}', 'NewsController@getNewsDetail')->where('newsId', '[0-9]+');

        Route::group(['prefix' => 'payment'], function () {
            Route::post('wxpay/app/prepay', 'PaymentController@wxpayAppPrepareTrade');
            Route::post('alipay/app/prepay', 'PaymentController@alipayAppPrepareTrade');
        });

        Route::get('message/list', 'MessageController@listMessages');
        Route::get('message/new', 'MessageController@listNewMessages');
        Route::get('check_new', 'MessageController@checkNew');
        Route::get('banner/list', 'BannerController@listBanners');
    });
});
Route::post('api/payment/wxpay/web/prepay', 'Api\PaymentController@wxpayWebPrepareTrade');
Route::post('api/payment/wxpay/pay/notify', 'Api\PaymentController@wxpayNotify');
Route::post('api/payment/wxpay/webpay/notify', 'Api\PaymentController@wxpayWebNotify');
Route::post('api/payment/alipay/pay/notify', 'Api\PaymentController@alipayNotify');

/*
 |------------------------------------------------------------------------
 | Routes for api team owner management
 |------------------------------------------------------------------------
 |
 */
Route::group([
    'prefix' => 'api/manage',
    'namespace' => 'Backstage',
    'middleware' => ['request.sign', 'auth', 'auth.device', 'team.inject'],
    ], function () {
    Route::get('act/list', 'ActivityController@getMangeActivitiesByTeam');
    Route::get('act/member/mobile', 'ActivityController@getActivityMemberPhone');
    Route::get('act/applicant/list', 'ActivityApplicantController@getApplicantsListForClientManage');
    Route::post('act/applicant/approve', 'ActivityApplicantController@batchApproveApplicant');
    Route::post('act/applicant/refuse', 'ActivityApplicantController@batchRefuseApplicant');
    Route::post('act/applicant/remark', 'ActivityApplicantController@remark');
    Route::post('act/applicant/vip/add', 'ActivityApplicantController@addSingleVip');
    Route::get('act/checkin/list', 'ActivityCheckInController@listForClientManage');
    Route::get('act/checkin/search', 'ActivityCheckInController@searchInfo');
    Route::post('act/notice/send', 'ActivityController@sendNoticeOfSmsForMass');
    Route::get('act/notice/send/times', 'ActivityController@restSendNoticesTimes');
    Route::get('act/notice/send/times', 'ActivityController@restSendNoticesTimes');
    Route::get('act/checkin/qrcode/list', 'ActivityCheckInController@getCheckInQRCodes');
    Route::post('act/checkin', 'ActivityCheckInController@manageCheckIn');
    Route::post('act/remove/checkin', 'ActivityCheckInController@manageRemoveCheckIn');
    Route::post('act/qrcode/checkin', 'ActivityCheckInController@qrcodeCheckIn');
});

/*
 |------------------------------------------------------------------------
 | Routes for admin
 |------------------------------------------------------------------------
 |
 */
Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['admin.driver.inject', 'csrf']], function () {
    Route::post('login', 'UserController@login');
    Route::get('logout', 'UserController@logout');

    Route::get('/', 'HomeController@login');
    Route::get('tag/list', 'HomeController@listTag');

    Route::group(['middleware' => ['admin.auth', 'admin.role.auth'], 'roles' => 'admin'], function () {
        Route::group(['prefix' => 'user'], function () {
            Route::get('list', 'UserController@listUsers');
            Route::post('password/reset', 'UserController@resetPassword');
            Route::post('add', 'UserController@add');
            Route::post('remove', 'UserController@remove');

            Route::group(['roles' => ['operator', 'accountant']], function () {
                Route::post('password/self/reset', 'UserController@resetSelfPassword');
                Route::get('password/update', 'UserController@renderUpdatePassword');
                Route::post('password/update', 'UserController@resetSelfPassword');
            });

            Route::post('password/reset', 'UserController@resetPassword');
            Route::get('', 'UserController@renderUserList');
            Route::get('create', 'UserController@renderCreateUser');
            Route::post('create', 'UserController@add');
            Route::delete('remove', 'UserController@remove');
        });

        Route::group(['roles' => 'operator'], function () {
            Route::post('notice/send', 'UserController@sendNotice');
            Route::get('notice/list', 'UserController@listNotices');
            Route::get('team/request/list', 'TeamController@listPendingRequests');
            Route::post('team/request/enrollment/approve', 'TeamController@approvePendingEnrollmentRequest');
            Route::post('team/request/enrollment/reject', 'TeamController@rejectPendingEnrollmentRequest');
            Route::post('team/request/update/approve', 'TeamController@approvePendingUpdateRequest');
            Route::post('team/request/update/reject', 'TeamController@rejectPendingUpdateRequest');
            Route::get('team/certification/list', 'TeamController@listPendingTeamsForCertification');
            Route::get('team/certification/info/list', 'TeamController@listCertifications');
            Route::post('team/certification/approve', 'TeamController@approvePendingTeamForCertification');
            Route::post('team/certification/reject', 'TeamController@rejectPendingTeamForCertification');
            Route::get('team/list', 'TeamController@listTeams');
            Route::post('team/freeze', 'TeamController@freezeTeam');
            Route::post('team/freeze/cancel', 'TeamController@cancelFreezeTeam');
            Route::post('team/forbidden', 'TeamController@forbiddenTeam');
            Route::post('team/forbidden/cancel', 'TeamController@cancelForbiddenTeam');
            Route::post('team/tag', 'TeamController@tagTeam');
            Route::get('teams', 'OperateController@teams');
            Route::get('teams/verify', 'OperateController@teamVerify');
            Route::get('teams/authentication', 'OperateController@teamAuthentication');
            Route::get('activities', 'OperateController@activities');
            Route::get('members', 'OperateController@members');
            Route::get('notices', 'OperateController@notices');
            Route::get('notices/list', 'OperateController@noticesList');
            Route::get('notices/system', 'OperateController@systemNotices');
            Route::post('upgrade/push', 'ActivityController@pushPlatformUpgrade');
            Route::get('tags', 'OperateController@tags');
            Route::get('client/user/list', 'OperateController@listClientUser');
            Route::get('client/user/detail', 'OperateController@showClientUserDetail');
            Route::group(['prefix' => 'activity'], function () {
                Route::post('list', 'ActivityController@searchActivityTitleByTagsAndStatus');
                Route::post('delete', 'ActivityController@deleteActivityById');
                Route::post('restore', 'ActivityController@restoreActivityById');
                Route::post('settags', 'ActivityController@update');
                Route::get('detail', 'ActivityController@getActivityById');
            });
        });

        Route::group(['roles' => 'accountant', 'prefix' => 'accountant'], function () {
            Route::get('', 'AccountantController@index');
            Route::get('team', 'AccountantController@team');
            Route::get('income/list', 'AccountantController@listIncome');
            Route::post('income/transfer/do', 'AccountantController@doIncomeTransfer');
            Route::post('income/transfer/confirm', 'AccountantController@confirmIncomeTransfer');
            Route::post('income/transfer/finish', 'AccountantController@finishIncomeTransfer');
        });
    });
});


Route::group([
    'namespace' => 'Activity',
    'prefix' => 'act',
    'middleware' => 'pm.act',
], function () {
    Route::get('{name}/login', 'LoginController@loginForm');
    Route::post('{name}/login', 'LoginController@login');
    Route::get('{name}/logout', 'LoginController@logout');
    Route::get('{name}/list/all', 'AuditController@listAll');
});
