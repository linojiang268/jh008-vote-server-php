<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\VerificationRepository as VerificationRepositoryContract;
use Jihe\Models\Verification;

class VerificationRepository implements VerificationRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\VerificationRepository::count()
     */
    public function count($mobile, $time = null, $all = true)
    {
        $query = Verification::where('mobile', $mobile)
                 // append the logic 'created_at <= now', which should
                 // always be true, but by appending this fact, we
                 // may make use of database index to scan faster
                             ->where('created_at', '<=', date('Y-m-d H:i:s'));
        
        if (!empty($time)) {
            $query->where('created_at', '>=', $time);
        }
        
        if (!$all) { // eliminate trashed ones
            $query->whereNull('deleted_at');
        }
        
        return $query->count();
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\VerificationRepository::add()
     */
    public function add(array $data)
    {
        Verification::create($data);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\findLastRequested::findDisposable()
     */
    public function findLastRequested($mobile, $expriy = null)
    {
        $verification = Verification::where('mobile', $mobile)
                                    ->where('expired_at', '>', $expriy ?: date('Y-m-d H:i:s'))
                                    ->whereNull('deleted_at')
                                    ->orderBy('expired_at', 'desc')
                                    ->first();
        
        return $verification ? $verification->toEntity() : null;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\VerificationRepository::remove()
     */
    public function remove($id)
    {
        // Eloquent Model::destroy() method can be used here. It loads data
        // from database ('SELECT') and then delete ('UPDATE', since we're using
        // soft deletes here) it. Here, we use query builder to issue just one
        // 'UPDATE' command to database.
        Verification::where('id', $id)
                    ->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\VerificationRepository::removeExpiredBefore()
     */
    public function removeExpiredBefore($expiredAt)
    {
        Verification::where('expired_at', '<', $expiredAt)
                    ->forceDelete();
    }
}
