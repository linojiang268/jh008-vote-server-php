<?php
namespace Jihe\Repositories;

use Cache;
use Jihe\Models\GaoXinVote as Vote;
use DB;

class GaoXinVoteRepository
{
    const USER_VOTE_COUNT_KEY = 'gaoxin_user_vote_count';
    const ALL_VOTE_COUNT_KEY = 'gaoxin_all_vote_count';
    const VOTER_TODAY_VOTE_COUNT_KEY = 'gaoxin_voter_today_vote_count';
    const GROUP_BY_USER_COUNT_SORT_KEY = 'gaoxin_group_by_user_sort_count';
    const ALL_USER_VOTE_COUNT_KEY = 'gaoxin_all_user_vote_sort_key';
    const ALL_USER_VOTE_SORT_KEY = 'gaoxin_all_user_vote_count_key';
    const USER_SORT_KEY = 'gaoxin_user_sort';
    const WX_VOTE_NUM = 5;
    const APP_VOTE_NUM = 4;

    public function add(Array $vote)
    {
        $insertID = Vote::create($this->morphToAttributes($vote))->id;
        if ($insertID) {
            $this->updateCache($vote['voter'], $vote['user_id']);
        }
        return $insertID;
    }

    private function updateCache($voter, $user)
    {
        $key = md5(self::ALL_VOTE_COUNT_KEY . date('Y-m-d'));
        if (Cache::has($key)) {
            Cache::increment($key, 1);
        } else {
            $this->findAllVoteCount();
        }
        $key = md5(self::VOTER_TODAY_VOTE_COUNT_KEY . date('Y-m-d')) . '_' . $voter;
        if (Cache::has($key)) {
            Cache::increment($key, 1);
        } else {
            $this->findTodayVoteCountByVoter($voter);
        }
        $key = md5(self::USER_VOTE_COUNT_KEY) . '_' . $user;
        if (Cache::has($key)) {
            Cache::increment($key, 1);
        } else {
            $this->findUserVoteCount([$user]);
        }
        $key = md5(self::ALL_USER_VOTE_COUNT_KEY . date('Y-m-d'));
        if (Cache::has($key)) {
            if (null !== $result = Cache::get($key)) {
                if (isset($result[$user])) {
                    $result[$user]++;
                } else {
                    $result[$user] = 1;
                }
                Cache::put($key, $result, 24 * 60);
            }
        } else {
            $this->findAllUserVteCount();
        }
    }

    /*
    private function sortData($data, $user){
        if(!empty($data) && $user > 0){
            $tmp = [];
            $previous = [];
            foreach($data as $index => $val){
                if($index == $user){
                    if($previous['user'] < $val['user']){
                        unset($tmp[$previous['user']]);
                        $tmp[$index] = $val;
                        $tmp[$previous['user']] = $previous;
                    }else{
                        $tmp[$index] = $val;
                    }
                }else{
                    $previous = $val;
                    $tmp[$index] = $val;
                }
            }
            $data = $tmp;
        }

        return $data;
    }
    */
    private function morphToAttributes($vote)
    {
        return [
            'voter'   => array_get($vote, 'voter'),
            'user_id' => array_get($vote, 'user_id'),
            'type'    => array_get($vote, 'type'),
        ];
    }

    public function findAllVoteCount()
    {
        $key = md5(self::ALL_VOTE_COUNT_KEY . date('Y-m-d'));
        if (Cache::has($key)) {
            if (null !== $count = Cache::get($key)) {
                return $count;
            }
        }
        $count = Vote::count();
        Cache::put($key, $count, 24 * 60);

        return $count;
    }

    public function findTodayVoteCountByVoter($voter)
    {
        $key = md5(self::VOTER_TODAY_VOTE_COUNT_KEY . date('Y-m-d')) . '_' . $voter;
        if (Cache::has($key)) {
            if (null !== $todayVote = Cache::get($key)) {
                return $todayVote;
            }
        }
        $todayVote = Vote::where('created_at', '>', date('Y-m-d 00:00:00'))->where('voter', $voter)->count();
        Cache::put($key, $todayVote, 24 * 60);

        return $todayVote;
    }

    public function findAllUserVteCount()
    {
        $key = md5(self::ALL_USER_VOTE_COUNT_KEY . date('Y-m-d'));
        if (Cache::has($key)) {
            if (null !== $result = Cache::get($key)) {
                return $result;
            }
        }
        $result = $this->findAllUserVoteCountDB();
        Cache::put($key, $result, 24 * 60);

        return $result;
    }

    public function findUserSort($user)
    {
        $ret = $this->findAllUserVteCount();
        $i = 1;
        if ($ret) {
            if (!isset($ret[$user])) {
                return null;
            }
            foreach ($ret as $index => $vote) {
                if ($vote > $ret[$user]) {
                    $i++;
                }
            }
            return $i;
        }
        return null;
    }


    private function findUserVoteCountDB($users)
    {
        $voteData = Vote::whereIn('user_id', $users)
            ->groupBy('user_id')
            ->select(DB::raw('count(user_id) as ci , user_id'))
            ->get();
        $result = array_fill_keys($users, 0);
        if ($voteData) {
            foreach ($voteData as $vote) {
                $result[$vote->user_id] = $vote->ci;
            }
        }

        return $result;
    }

    public function findUserVoteCount($users)
    {
        $result = array_fill_keys($users, 0);
        $ids = [];
        if ($users) {
            foreach ($users as $user) {
                $key = md5(self::USER_VOTE_COUNT_KEY) . '_' . $user;
                if (Cache::has($key)) {
                    $result[$user] = Cache::get($key);
                } else {
                    $ids[] = $user;
                }
            }
            if ($ids) {
                $ret = $this->findUserVoteCountDB($ids);
                if ($ret) {
                    foreach ($ret as $index => $vote) {
                        $result[$index] = $vote;
                        $key = md5(self::USER_VOTE_COUNT_KEY) . '_' . $index;
                        Cache::put($key, $vote, 24 * 60);
                    }
                }
            }
        }

        return $result;
    }

    private function findAllUserVoteCountDB()
    {
        $voteCounts = Vote::groupBy('user_id')
            ->select(DB::raw('count(1) as ci, user_id'))
            ->orderby('ci', 'desc')
            ->orderby('id', 'desc')
            ->get()
            ->all();
        $result = [];
        if ($voteCounts) {
            foreach ($voteCounts as $index => $voteCount) {
                $result[$voteCount->user_id] = $voteCount->ci;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function findVoteCountSort()
    {
        $key = md5(self::ALL_USER_VOTE_SORT_KEY . date('Y-m-d'));
        if (Cache::has($key)) {
            if (null !== $result = Cache::get($key)) {
                return $result;
            }
        }
        $voteCounts = Vote::groupBy('user_id')
            ->select(DB::raw('count(1) as ci, user_id'))
            ->orderby('ci', 'desc')
            ->orderby('id', 'desc')
            ->get()
            ->all();
        $result = [];
        if ($voteCounts) {
            foreach ($voteCounts as $index => $voteCount) {
                $result[$voteCount->user_id]['user'] = $voteCount->user_id;
                $result[$voteCount->user_id]['count'] = $voteCount->ci;
                $result[$voteCount->user_id]['sort'] = $index + 1;
            }
        }
        Cache::put($key, $result, 3);

        return $result;
    }

    public function getAttendantIds($page, $size)
    {
        if ($size <= 0 || $page <= 0) {
            return null;
        }
        $sortResult = $this->findVoteCountSort();
        if (empty($sortResult)) {
            return null;
        }
        $totalPage = intval(ceil(count($sortResult) / $size));
        if ($totalPage < $page) {
            return null;
        }
        $data = array_slice($sortResult, ($page - 1) * $size, $size);
        $ids = [];
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $ids[] = $val['user'];
            }
        }
        return [count($sortResult), $ids];
    }

    public function findUserVoteSort($user)
    {
        $sortResult = $this->findVoteCountSort();
        if ($sortResult[$user]) {
            return $sortResult[$user];
        } else {
            return ['user' => $user, 'count' => 0, 'sort' => 0];
        }
    }

}
