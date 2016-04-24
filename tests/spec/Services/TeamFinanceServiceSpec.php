<?php
namespace spec\Jihe\Services;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Jihe\Services\TeamService;
use Jihe\Services\TeamFinanceService;
use Jihe\Services\ActivityService;
use Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository;
use Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository;
use Jihe\Entities\ActivityEnrollIncome;


class TeamFinanceServiceSpec extends ObjectBehavior
{
    function let(TeamService $teamService,
                 ActivityService $activityService,
                 ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
                 ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $this->beAnInstanceOf(\Jihe\Services\TeamFinanceService::class, [
            $teamService,
            $activityService,
            $activityEnrollPaymentRepository,
            $activityEnrollIncomeRepository,
        ]);
    }

    //=============================================
    //          doIncomeTransfer 
    //=============================================
    function it_do_income_transfer_successful(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $income = (new ActivityEnrollIncome)
            ->setId(1)
            ->setStatus(1);
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn($income);

        $activityEnrollIncomeRepository->updateStatus($income->getId(), 2)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        
        $this->doIncomeTransfer(1)->shouldBe(true);
    }

    function it_do_income_transfer_faild_if_wrong_init_status(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $income = (new ActivityEnrollIncome)
            ->setId(1)
            ->setStatus(2);
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn($income);

        $activityEnrollIncomeRepository->updateStatus($income->getId(), 2)
            ->shouldNotBeCalled(1);
        
        $this->doIncomeTransfer(1)->shouldBe(false);
    }

    //=============================================
    //          finishIncome
    //=============================================
    function it_finish_income_transfer_successful(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $income = (new ActivityEnrollIncome)
            ->setId(1)
            ->setStatus(2);
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn($income);

        $activityEnrollIncomeRepository->updateStatus($income->getId(), 3)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        
        $this->finishIncomeTransfer(1)->shouldBe(true);
    }

    function it_finish_income_transfer_faild_if_wrong_init_status(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $income = (new ActivityEnrollIncome)
            ->setId(1)
            ->setStatus(1);
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn($income);

        $activityEnrollIncomeRepository->updateStatus(Argument::any(), Argument::any())
            ->shouldNotBeCalled(1);
        
        $this->finishIncomeTransfer(1)->shouldBe(false);
    }

    function it_finish_income_transfer_faild_if_income_not_exists(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn(null);

        $activityEnrollIncomeRepository->updateStatus(Argument::any(), Argument::any())
            ->shouldNotBeCalled();
        
        $this->finishIncomeTransfer(1)->shouldBe(false);

    }

    //=============================================
    //          confirmIncome
    //=============================================
    public function it_confirm_income_transfer_successfully(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $income = (new ActivityEnrollIncome)
            ->setId(1)
            ->setTotalFee(100)
            ->setTransferedFee(10)
            ->setStatus(2);
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn($income);

        $activityEnrollIncomeRepository
            ->updateConfirm($income->getId(), 20, 'http://domain/evidence.jpg')
            ->willReturn(true);

        $this->confirmIncomeTransfer(1, 20, 'http://domain/evidence.jpg')
             ->shouldBe(null);
    }

    public function it_throw_exception_income_not_exists_when_confirm(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn(null);

        $activityEnrollIncomeRepository->updateStatus(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('您不能执行确认转账操作'))
             ->duringConfirmIncomeTransfer(1, 20, 'http://domain/evidence.jpg');
    }

    public function it_throw_exception_status_invalid_when_confirm(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $income = (new ActivityEnrollIncome)
            ->setId(1)
            ->setTotalFee(100)
            ->setTransferedFee(100)
            ->setStatus(3);
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn($income);

        $activityEnrollIncomeRepository->updateStatus(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('您不能执行确认转账操作'))
             ->duringConfirmIncomeTransfer(1, 20, 'http://domain/evidence.jpg');
    }

    public function it_throw_exception_fee_too_large_when_confirm(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $income = (new ActivityEnrollIncome)
            ->setId(1)
            ->setTotalFee(100)
            ->setTransferedFee(90)
            ->setStatus(2);
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn($income);

        $activityEnrollIncomeRepository->updateStatus(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('您当前转款的金额超出了允许的额度，还剩' .
                                          '0.1元可转'))
             ->duringConfirmIncomeTransfer(1, 20, 'http://domain/evidence.jpg');
    }

    public function it_throw_exception_save_failed_when_confirm(
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $income = (new ActivityEnrollIncome)
            ->setId(1)
            ->setTotalFee(100)
            ->setTransferedFee(10)
            ->setStatus(2);
        $activityEnrollIncomeRepository->findOneById(1)
            ->shouldBeCalledTimes(1)
            ->willReturn($income);

        $activityEnrollIncomeRepository
            ->updateConfirm($income->getId(), 20, 'http://domain/evidence.jpg')
            ->willReturn(false);

        $this->shouldThrow(new \Exception('执行确认转账操作失败'))
             ->duringConfirmIncomeTransfer(1, 20, 'http://domain/evidence.jpg');
    }
}
