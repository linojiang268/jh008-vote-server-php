<?php
namespace Jihe\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Jihe\Models\XinNianVote;
use Jihe\Models\YoungSingle;
use Jihe\Utils\PaginationUtil;

class YoungSingleRepository
{
    const LIST_KEY = 'young_single_list';
    const LIST_SIZE = 10;

    const SORT_LIST_KEY = 'young_single_sort_list';


    /**
     * @param YoungSingle $youngSingle
     * @return bool
     */
    public function add(YoungSingle $youngSingle)
    {
        if (!is_null($this->findByMobile($youngSingle->mobile))) {
            return false;
        }

        $youngSingle->status = YoungSingle::STATUS_PENDING;
        return $youngSingle->save();
    }

    /**
     * @param $mobile
     * @return null
     */
    public function findByMobile($mobile)
    {
        return YoungSingle::where('mobile', $mobile)->first();
    }

    /**
     * approve given youngSingles
     *
     * @param stdClass|array $youngSingles   stdClasses of attendants
     * @return mixedll
     */
    public function approve($youngSingles)
    {
        $fp = fopen(storage_path('xinnian_approve.lock'), 'w+');
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }

        $ex = null;
        try {
            if (!is_array($youngSingles)) {
                $youngSingles = [$youngSingles];
            }
            $total = YoungSingle::max('order_id');
            foreach ($youngSingles as $key => $youngSingle ) {
                $rst = 1 === YoungSingle::where('id', $youngSingle->id)
                        ->where('status', YoungSingle::STATUS_PENDING)
                        ->update(['status' => YoungSingle::STATUS_APPROVED ,
                                  'order_id' => ++$total]);
                if($rst){
                    $youngSingle->order_id = $total;
                    $this->notifyChangedListInCache($youngSingle);
                }else{
                    $total--;
                }
            }
        } catch (\Exception $e) {
            $ex = $e;
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        if ($ex != null) {
            return false;
        }
        return true;
    }

    private function remove($youngSingles, $status = YoungSingle::STATUS_PENDING)
    {
        if (!is_array($youngSingles)) {
            $youngSingles = [$youngSingles];
        }

        $params = $youngSingles;
        array_push($params, $status);

        return count($youngSingles) === YoungSingle::whereIn('id', $youngSingles)
            ->where('status', $status)
            ->delete();
    }

    /**
     * remove given applicants
     *
     * @param integer|array $applicants   ids of applicants
     * @return mixed
     */
    public function removeApplicants($applicants)
    {
        return $this->remove($applicants, YoungSingle::STATUS_PENDING);
    }

    /**
     * remove given $youngSingles
     *
     * @param integer|array $youngSingles   ids of $youngSingles
     * @return mixed
     */
    public function removeYoungSingles($youngSingles)
    {
        $rst = $this->remove($youngSingles, YoungSingle::STATUS_APPROVED);

        if ($rst) {
            $this->clearCache();
        }

        return $rst;
    }

    /**
     * @param $youngSingle  id of youngSingle
     * @return null|YoungSingle
     */
    public function find($youngSingle)
    {
        return YoungSingle::where('id', $youngSingle)->first();
    }

    /**
     * @param $youngSingle  id of youngSingle
     * @return null|YoungSingle
     */
    public function findByNumber($number)
    {
        return YoungSingle::where('order_id', $number)->first();
    }


    /**
     * get approved youngSingles
     *
     * @param $page  index of page
     * @param $size  size of page
     * @return array [total, attendants]
     */
    public function listApprovedYoungSingles($page, $size, $cache = false)
    {
        if ($cache) {
            $rst = $this->getYoungSinglesListInCache($page, $size);
            if (!empty($rst)) {
                return $rst;
            }
        }

        $query = YoungSingle::where('status', YoungSingle::STATUS_APPROVED)->orderBy('order_id', 'asc');

        $rst = [
            $query->count(),
            $query->forPage($page, $size)->get()->all(),
        ];

        if ($cache) {
            $this->storeYoungSinglesListInCache($page, $rst);
        }
        return $rst;
    }

    /**
     * get pending youngSingles
     *
     * @param $page  index of page
     * @param $size  size of page
     * @return array [total, youngSingles]
     */
    public function listPendingYoungSingles($page, $size)
    {
        $query = YoungSingle::where('status', YoungSingle::STATUS_PENDING);

        return [
            $query->count(),
            $query->forPage($page, $size)->get()->all(),
        ];
    }

    /**
     * sane page of youngSingle in youngSingles
     *
     * @param $youngSingle stdClass of youngSingle
     * @param $size
     * @return mixed   [index of page, index in page, count]
     */
    private function sanePage($youngSingle, $size)
    {
        $total = YoungSingle::where('status', YoungSingle::STATUS_APPROVED)
            ->where('order_id', '<', $youngSingle->order_id)->count();

        $count = $total + 1;
        return [intval(ceil($count / $size)), ($count - 1) % $size, $count];
    }

    private function countYoungSingles()
    {
        return YoungSingle::where('status', YoungSingle::STATUS_APPROVED)->count();
    }

    //=============================================
    //             Cache of attendant
    //=============================================
    public function getYoungSinglesListInCache($page)
    {
        $key = self::createCacheKey($page);
        if (Cache::has($key)) {
            return json_decode(Cache::get($key));
        }
        return null;
    }

    public function storeYoungSinglesListInCache($page, $youngSinglesRst)
    {
        Cache::put(self::createCacheKey($page),
            json_encode($youngSinglesRst), Carbon::now()->addDay(1));
    }

    public function clearYoungSinglesInCache($page, $key = self::LIST_KEY)
    {
        if (Cache::has(self::createCacheKey($page, $key))) {
            Cache::forget(self::createCacheKey($page, $key));
        }
    }

    /**
     * sane page of given YoungSingle in list
     *
     * @param $youngSingle  stdClass of YoungSingle
     */
    private function notifyChangedListInCache($youngSingle)
    {
        list($page, $indexInPage, $total) = $this->sanePage($youngSingle, self::LIST_SIZE);

        $rst = $this->getYoungSinglesListInCache($page);
        if (empty($rst)) {
            $this->updateYoungSinglesSmallerThan($page, $total);
            return;
        }

        list($total, $youngSingles) = $rst;
        array_splice($youngSingles, $indexInPage, 0, 'x');
        $youngSingles[$indexInPage] = $youngSingle;

        if (count($youngSingles) < self::LIST_SIZE + 1) {
            $this->storeYoungSinglesListInCache($page, [$total + 1, $youngSingles]);
            $this->updateYoungSinglesSmallerThan($page, $total + 1);
            return;
        }

        array_pop($youngSingles);
        $this->storeYoungSinglesListInCache($page, [$total + 1, $youngSingles]);

        $this->updateYoungSinglesSmallerThan($page, $total + 1);
        $this->clearYoungSinglesLarghThan($page, $total + 1);
    }

    private function clearYoungSinglesLarghThan($page, $total, $key = self::LIST_KEY)
    {
        $pages = PaginationUtil::count2Pages($total, self::LIST_SIZE);
        for ($page += 1;$page <= $pages; $page++)
        {
            $this->clearYoungSinglesInCache($page, $key);
        }
    }

    private function updateYoungSinglesSmallerThan($page, $total)
    {
        if (1 == $page) {
            return;
        }

        for ($i = $page - 1;$i >= 1; $i--) {
            $rst = $this->getYoungSinglesListInCache($i);
            if (empty($rst)) continue;
            $this->storeYoungSinglesListInCache($i, [$total, $rst[1]]);
        }
    }

    private static function createCacheKey($page, $key = self::LIST_KEY) {
        return md5($key) . '_' . date('Y-m-d') . '_' . $page;
    }

    public function findByIds($youngSingleIds)
    {
        if(empty($youngSingleIds)){
            return [];
        }
        $data = array_fill_keys($youngSingleIds, []);
        $key = md5(self::SORT_LIST_KEY . date('Y-m-d').'_'.implode(',',$youngSingleIds));
        if (Cache::has($key)) {
            if (null !== $youngSingles = Cache::get($key)) {
                return $youngSingles;
            }
        }
        $rst = YoungSingle::whereIn('id', $youngSingleIds)->get()->all();
        array_map(function ($youngSingle)  use (&$data) {
            $data[$youngSingle->id] = $youngSingle;
        }, $rst);
        $data = array_values(array_filter($data));
        Cache::put($key, $data, 10);

        return $data;
    }

    private function clearCache()
    {
        $total = $this->countYoungSingles() + 1;
        $this->clearYoungSinglesLarghThan(0, $total, self::LIST_KEY);
    }
}