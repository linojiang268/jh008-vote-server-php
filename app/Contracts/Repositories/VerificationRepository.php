<?php
namespace Jihe\Contracts\Repositories;

interface VerificationRepository
{
    /**
     * get the message count of given user (denoted by his/her mobile)
     * from starting time (optional) to now
     *
     * @param string $mobile   mobile#
     * @param string $time     (optional) starting time in 'Y-m-d H:i:s' format
     * @param bool   $all      true for all messages, false for valid ones only
     */
    public function count($mobile, $time = null, $all = true);
    
    /**
     * add a new verification code
     *
     * @param array $verification   holds data for verification
     * @return void
     */
    public function add(array $verification);
    
    /**
     * find the verification that is usable. multiple verifications
     * can be requested, what's needed is the last one, which is
     * usable.
     *
     * @param string $mobile
     * @param string $expiry  expiry time of the code, default to now
     * @return \Jihe\Entities\Verification|null      the verification
     */
    public function findLastRequested($mobile, $expiry = null);
    
    /**
     * remove given verification
     * @param int $id   id of the verification code to remove
     */
    public function remove($id);

    /**
     * remove expired verification before given expired time
     * @param string $expiredAt   expired time in Y-m-d H:i:s format
     */
    public function removeExpiredBefore($expiredAt);
}
