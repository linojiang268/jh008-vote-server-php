<?php

namespace Jihe\Services;

use Jihe\Contracts\Repositories\ActivityCheckInRepository;
use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Contracts\Repositories\ActivityCheckInQRCodeRepository;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Exceptions\User\UserNotExistsException;

class ActivityCheckInService
{

    private $activityCheckInRepository;
    private $activityMemberRepository;
    private $activityCheckInQRCodeRepository;
    private $activityService;

    public function __construct(ActivityCheckInRepository $activityCheckInRepository,
                                ActivityMemberRepository $activityMemberRepository,
                                ActivityCheckInQRCodeRepository $activityCheckInQRCodeRepository,
                                UserRepository $userRepository,
                                ActivityService $activityService
    )
    {
        $this->activityCheckInRepository = $activityCheckInRepository;
        $this->activityMemberRepository = $activityMemberRepository;
        $this->activityCheckInQRCodeRepository = $activityCheckInQRCodeRepository;
        $this->userRepository = $userRepository;
        $this->activityService = $activityService;
    }

    public function getCheckInList($userId, $activityId)
    {
        $activityQrcodes = $this->activityCheckInQRCodeRepository->all($activityId);
        if (empty($activityQrcodes)) {
            return null;
        }
        $activitySteps = array_column($activityQrcodes, 'step');
        $checkedStepsList = $this->activityCheckInRepository->all($activityId, $userId);
        $checkedSteps = array_column($checkedStepsList, 'step');
        $checkInList = [];
        foreach ($activitySteps as $stepRequired) {
            $stepStatus = ['step' => $stepRequired, 'status' => 1];
            if (!in_array($stepRequired, $checkedSteps)) {
                $stepStatus['status'] = 0;
            }
            $checkInList[] = $stepStatus;
        }
        return $checkInList;
    }

    /**
     * Get user checkIn list for client management
     *
     * @param integer $activityId   activity id which checkin belongs to
     * @param integer $type         0 - fetch waiting list
     *                              1 - fetch done list
     * @param integer $page
     * @param integer $size
     *
     * @return array                0 - integer total records
     *                              1 - \Illuminate\Support\Collection
     *                                  elements are \Jihe\Entities\ActivityMember
     */
    public function getUserCheckInListForClientManage(
        $activityId, $type, $page, $size
    )
    {
        return $this->activityMemberRepository->getCheckinList(
            $activityId, $type, $page, $size
        );
    }

    /**
     * Search user checkin info by mobile or name
     *
     * @param integer $activityId
     * @param string $mobile
     * @param string $name
     *
     * @return \Illuminate\Support\Colletion    element is \Jihe\Entities\ActivityMember
     */
    public function searchCheckinInfo($activityId, $mobile = null, $name = null)
    {
        if ( ! $mobile && ! $name) {
            return collect([]);
        }

        return $this->activityMemberRepository->searchCheckinInfo($activityId, $mobile, $name);
    }

    /**
     * Get activity all check in list, arranged by step
     *
     * @param integer $activity activity id
     * @param integer $step     check in step
     * @param integer $page     the current page number
     * @param integer $pageSize the number of data per page
     *
     * @return array                    [0] total check in count
     *                                  [1] check in data, each element key as below:
     *                                  id          integer     check in id
     *                                  user_id     integer     user id
     *                                  nick_name   string      user nick name
     *                                  mobile      string      user mobile number
     *                                  step        integer     check in step
     *                                  created_at  datetime    check in time
     */
    public function getActivityAllCheckInListByStep($activity, $step, $page, $pageSize)
    {
        return $this->activityCheckInRepository->getAllByActivityAndStep(
            $activity, $step, $page, $pageSize);
    }

    /**
     * 1 user check in
     * 2 manager process user check in
     *
     * @param   int    $userId     user ID
     * @param   int    $activityId activity ID
     * @param   int    $thisStep   check in step
     * @param null|int $processId  team creator or team manger user ID
     *
     * @return mixed
     * @throws \Exception
     */
    public function checkIn($userId, $activityId, $thisStep, $processId = 0)
    {
        list($activitySteps, $checkedSteps, $checkedStepIds) = $this->getCheckInRelatedDataInDB($userId, $activityId);
        list($checkStatus, $currentStep) = $this->makeCheckInData($activitySteps, $checkedSteps);
        if ($processId > 0) {
            //check process user rule
            $this->activityService->getActivityById($activityId, $processId);
        }
        $shouldCheck = true;
        if (in_array($thisStep, $checkedSteps)) {
            $checkStatus['message'] = '你已经签过到了';
            $shouldCheck = false;
            if(isset($checkedStepIds[$thisStep])){
                if($checkedStepIds[$thisStep]['processId'] > 0 && $processId == 0){
                    $this->activityCheckInRepository->updateProcessId($checkedStepIds[$thisStep]['id']);
                }
            }
        } elseif ($thisStep != $currentStep) {
            throw new \Exception("步骤{$currentStep}尚未签到");
        }
        //no check in
        if ($shouldCheck) {
            if (!$this->activityCheckInRepository->add($userId, $activityId, $thisStep, $processId)) {
                throw new \Exception("签到失败，请联系现场工作人员");
            } else {
                $this->activityMemberRepository->markAsCheckin($userId, $activityId);
                $checkStatus['message'] = "签到成功";
                $checkStatus['check_list'][$thisStep - 1]['status'] = 1;
            }
        }

        return $checkStatus;
    }

    /**
     * delete manager add check in data
     *
     * @param int $userId
     * @param int $activityId
     * @param int $thisStep
     * @param int $processId
     *
     * @return mixed
     * @throws \Exception
     */
    public function removeCheckIn($userId, $activityId, $thisStep, $processId)
    {
        $this->activityService->getActivityById($activityId, $processId);
        list($activitySteps, $checkedSteps, $checkedStepIds) = $this->getCheckInRelatedDataInDB($userId, $activityId);
        if (in_array($thisStep, $checkedSteps)) {
            if($checkedStepIds[$thisStep]['processId'] == $processId){
                $this->activityMemberRepository->unsetCheckIn($userId, $activityId);
                return $this->activityCheckInRepository->delete($checkedStepIds[$thisStep]['id']);
            }else{
                throw new \Exception("非团长签到,不可取消");
            }
        }else{
            throw new \Exception("签到数据不存在");
        }
    }

    /**
     * First step quick check in. H5 user can finished first check in without scannig qrcode
     *
     * @param string  $mobile user mobile number
     * @param integer $activityId
     *
     * @throws UserNotExistsException
     * @throws \Exception   if error happend, exception will be throw
     */
    public function firstStepQuickCheckIn($mobile, $activityId)
    {
        $userId = $this->userRepository->findId($mobile);
        if (!$userId) {
            throw new UserNotExistsException($mobile, '您还未注册，请先注册，请联系现场工作人员');
        }
        $this->checkIn($userId, $activityId, 1);
    }

    /**
     * get check in related data in database
     *
     * @param int $userId
     * @param int $activityId
     *
     * @return array
     * @throws \Exception
     */
    private function getCheckInRelatedDataInDB($userId, $activityId)
    {
        if (!$this->activityMemberRepository->exists($activityId, $userId)) {
            throw new \Exception('非活动成员');
        }
        //get activity check in qr code
        $activityQrCodes = $this->activityCheckInQRCodeRepository->all($activityId);
        if (empty($activityQrCodes)) {
            throw new \Exception('该活动不需要签到');
        }
        //get activity check in step
        $activitySteps = array_column($activityQrCodes, 'step');
        if (empty($activitySteps)) {
            throw new \Exception('未查询到活动二维码');
        }
        //get activity user check in record
        $checkedStepsList = $this->activityCheckInRepository->all($activityId, $userId);
        $checkedSteps = [];
        $checkedStepIds = [];
        if($checkedStepsList){
            foreach($checkedStepsList as $checkedStep){
                $checkedSteps[] = $checkedStep['step'];
                $checkedStepIds[$checkedStep['step']] = [
                    'id' => $checkedStep['id'],
                    'processId' => $checkedStep['process_id']
                ];
            }
        }

        return [$activitySteps, $checkedSteps, $checkedStepIds];
    }

    /**
     * process activity check in related data
     *
     * @param array $activitySteps
     * @param array $checkedSteps
     *
     * @return array
     */
    private function makeCheckInData($activitySteps, $checkedSteps)
    {
        $checkStatus = ['message' => '', 'check_list' => []];
        //default user not check in
        $currentStep = $activitySteps[0];
        $found = false;
        foreach ($activitySteps as $stepRequired) {
            $stepStatus = ['step' => $stepRequired, 'status' => 1];
            if (!in_array($stepRequired, $checkedSteps)) {
                $stepStatus['status'] = 0;
                if (!$found) {
                    //user should check in step
                    $currentStep = $stepRequired;
                    $found = true;
                }
            }
            $checkStatus['check_list'][] = $stepStatus;
        }

        return [$checkStatus, $currentStep];
    }
}
