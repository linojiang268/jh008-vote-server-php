<?php
namespace Jihe\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Jihe\Utils\PaginationUtil;

class AttendantRepository
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;

    const ATTENDANT_LIST_KEY = 'attendant_list';
    const ATTENDANT_LIST_SIZE = 10;

    /**
     * @param array $attendant  keys taken:
     *                           - name
     *                           - gender  male:1, female:2
     *                           - age
     *                           - height
     *                           - speciality
     *                           - school
     *                           - major
     *                           - education
     *                           - graduation_time
     *                           - ident_code
     *                           - mobile
     *                           - wechat_id
     *                           - email
     *                           - images_url   array of url
     *                           - motto
     *                           - introduction
     */
    public function add(array $attendant)
    {
        if (!is_null($this->findIdByMobile(array_get($attendant, 'mobile'))) ||
            !is_null($this->findIdByIdentCode(array_get($attendant, 'ident_code')))) {
            return false;
        }

        $images = array_get($attendant, 'images_url');
        array_set($attendant, 'cover_url', $images[0]);
        array_set($attendant, 'images_url', json_encode($images));

        // status    0:pending, 1:approved
        array_set($attendant, 'status', self::STATUS_PENDING);
        array_set($attendant, 'created_at', date('Y-m-d H:i:s'));
        array_set($attendant, 'updated_at', date('Y-m-d H:i:s'));

        return \DB::insert('insert into attendants (
                    name, gender, age, height, speciality,
                    school, major, education, graduation_time,
                    ident_code, mobile, wechat_id, email,
                    cover_url, images_url, motto, introduction, status,
                    created_at, updated_at)
                    values (
                    :name, :gender, :age, :height, :speciality,
                    :school, :major, :education, :graduation_time,
                    :ident_code, :mobile, :wechat_id, :email,
                    :cover_url, :images_url, :motto, :introduction, :status,
                    :created_at, :updated_at)', $attendant);
    }

    /**
     * find id of attendant by given mobile
     *
     * @param $mobile
     * @return null|integer
     */
    public function findIdByMobile($mobile)
    {
        $rst = \DB::select('select id from attendants where mobile = :mobile', ['mobile' => $mobile]) ?: null;
        if (empty($rst)) {
            return null;
        }

        return $rst[0]->id;
    }

    /**
     * find id of attendant by given ident code
     *
     * @param $mobile
     * @return null|integer
     */
    public function findIdByIdentCode($identCode)
    {
        $rst = \DB::select('select id from attendants where ident_code = :ident_code', ['ident_code' => $identCode]) ?: null;
        if (empty($rst)) {
            return null;
        }

        return $rst[0]->id;
    }

    /**
     * approve given attendant
     *
     * @param stdClass|array $attendants   stdClasses of attendants
     * @return mixed
     */
    public function approve($attendants)
    {
        if (!is_array($attendants)) {
            $attendants = [$attendants];
        }

        $attendantIds = array_map(function ($attendant) {
            return $attendant->id;
        }, $attendants);

        $rst = count($attendants) === \DB::update(
            'update attendants set status = 1 where id in (' .
            implode(array_fill(0, count($attendants), '?'), ',') .
            ') and status=0',
            $attendantIds
        );

        if ($rst) {
            foreach ($attendants as $value) {
                $this->notifyChangedListInCache($value);
            }
        }
        return $rst;
    }

    /**
     * remove given attendant
     *
     * @param integer|array $attendants   ids of attendants
     * @return mixed
     */
    public function remove($attendants)
    {
        if (!is_array($attendants)) {
            $attendants = [$attendants];
        }

        return count($attendants) === \DB::delete(
            'delete from attendants where id in (' .
            implode(array_fill(0, count($attendants), '?'), ',') . ')',
            $attendants
        );
    }

    /**
     * @param $attendant  id of attendant
     */
    public function find($attendant)
    {
        $rst = \DB::select('select * from attendants where id = ?', [$attendant]);
        if (empty($rst)) {
            return null;
        }

        return $rst[0];
    }

    /**
     * get approved attendants
     *
     * @param $page  index of page
     * @param $size  size of page
     * @return array [total, attendants]
     */
    public function listApprovedAttendants($page, $size, $cache = false)
    {
        if ($cache) {
            $rst = $this->getAttendantsListInCache($page, $size);
            if (!empty($rst)) {
                return $rst;
            }
        }

        $countRst = \DB::select('select count(1) AS total from attendants where status=' . self::STATUS_APPROVED);
        $attendants = \DB::select('select * from attendants where status=? order by id asc limit ?,?',
                                  [
                                      self::STATUS_APPROVED,
                                      ($page - 1) * $size,
                                      $size
                                  ]);

        $rst = [
            $countRst[0]->total,
            $attendants,
        ];

        if ($cache) {
            $this->storeAttendantsListInCache($page, $rst);
        }
        return $rst;
    }

    /**
     * get pending attendants
     *
     * @param $page  index of page
     * @param $size  size of page
     * @return array [total, attendants]
     */
    public function listPendingAttendants($page, $size)
    {
        $countRst = \DB::select('select count(1) AS total from attendants where status=' . self::STATUS_PENDING);
        $attendants = \DB::select('select * from attendants where status=' . self::STATUS_PENDING . ' limit ?,?', [($page - 1) * $size, $size]);

        return [
            $countRst[0]->total,
            $attendants,
        ];
    }

    /**
     * sane page of attendant in attendants
     *
     * @param $attendant stdClass of attendant
     * @param $size
     * @return mixed   [index of page, index in page, count]
     */
    private function sanePage($attendant, $size)
    {
        $countRst = \DB::select('select count(1) AS total from attendants where status=? and id<?', [self::STATUS_APPROVED, $attendant->id]);

        $count = ($countRst[0]->total + 1);
        return [intval(ceil($count / $size)), ($count - 1) % $size, $count];
    }

    //=============================================
    //             Cache of attendant
    //=============================================
    public function getAttendantsListInCache($page)
    {
        $key = self::createCacheKey($page);
        if (Cache::has($key)) {
            return json_decode(Cache::get($key));
        }
        return null;
    }

    public function storeAttendantsListInCache($page, $attendantsRst)
    {
        Cache::put(self::createCacheKey($page),
            json_encode($attendantsRst), Carbon::now()->addDay(1));
    }

    public function clearAttendantsInCache($page)
    {
        if (Cache::has(self::createCacheKey($page))) {
            Cache::forget(self::createCacheKey($page));
        }
    }

    /**
     * sane page of given attendant in list
     *
     * @param $attendant  stdClass of attendant
     */
    private function notifyChangedListInCache($attendant)
    {
        list($page, $indexInPage, $total) = $this->sanePage($attendant, self::ATTENDANT_LIST_SIZE);

        $rst = $this->getAttendantsListInCache($page);
        if (empty($rst)) {
            $this->updateAttendantsSmallerThan($page, $total);
            return;
        }

        list($total, $attendants) = $rst;
        array_splice($attendants, $indexInPage, 0, 'x');
        $attendants[$indexInPage] = $attendant;

        if (count($attendants) < self::ATTENDANT_LIST_SIZE + 1) {
            $this->storeAttendantsListInCache($page, [$total + 1, $attendants]);
            $this->updateAttendantsSmallerThan($page, $total + 1);
            return;
        }

        array_pop($attendants);
        $this->storeAttendantsListInCache($page, [$total + 1, $attendants]);

        $this->updateAttendantsSmallerThan($page, $total + 1);
        $this->clearAttendantsLarghThan($page, $total + 1);
    }

    private function clearAttendantsLarghThan($page, $total)
    {
        $pages = PaginationUtil::count2Pages($total, self::ATTENDANT_LIST_SIZE);
        for ($page += 1;$page <= $pages; $page++)
        {
            $this->clearAttendantsInCache($page);
        }
    }

    private function updateAttendantsSmallerThan($page, $total)
    {
        if (1 == $page) {
            return;
        }

        for ($i = $page - 1;$i >= 1; $i--) {
            $rst = $this->getAttendantsListInCache($i);
            if (empty($rst)) continue;
            $this->storeAttendantsListInCache($i, [$total, $rst[1]]);
        }
    }

    private static function createCacheKey($page) {
        return md5(self::ATTENDANT_LIST_KEY) . '_' . $page;
    }
}