<?php
namespace Jihe\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use \Illuminate\Support\Facades\DB;

trait GeometryTrait
{
    /**
     * The magic method that covers the parent class,
     * the insertion of the data processing of the point,
     * line and surface
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function __set($key, $value)
    {
        $geofields = $this->geofields;
        if (array_key_exists($key, $geofields)) {
            if (empty($value)) {
                return null;
            }
            $value = $this->makeValueToSetting($value, $geofields[$key]);
        }
        return parent::__set($key, $value);
    }

    /**
     * The magic method that covers the parent class,
     * the insertion of the data processing of the point,
     * line and surface
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function __get($key)
    {
        $geofields = $this->geofields;
        $value = parent::__get($key);
        if (array_key_exists($key, $geofields)) {
            if (empty($value)) {
                return null;
            }
            return $this->makeValueToGetting($value, $geofields[$key]);
        }
        return $value;
    }

    /**
     * @param  array  $value
     * @param  string $type
     *
     * @return array|string|null
     */
    private function makeValueToSetting($value, $type)
    {
        switch ($type) {
            case 'point':
                return $this->geometrySetPoint($value);
            case 'linestring':
                return $this->geometrySetLineString($value);
            case 'polygon':
                return $this->geometrySetPolygon($value);
            default:
                return $value;
        };
    }

    /**
     * @param  array  $value
     * @param  string $type
     *
     * @return array|string|null
     */
    private function makeValueToGetting($value, $type)
    {
        $point = $this->getPoints($value);
        switch ($type) {
            case 'point':
                return isset($point[0]) ? $point[0][0] : null;
            case 'linestring':
                return isset($point[0]) ? $point[0] : null;
            case 'polygon':
                return $point;
            default:
                return $value;
        };
    }

    /**
     * @param  array
     *
     * @return \Illuminate\Support\Facades\DB
     */
    private function geometrySetPoint($value)
    {
        if (!$this->chechData($value)) {
            return null;
        }
        return DB::raw('GeomFromText(\'POINT(' . $value[0] . ' ' . $value[1] . ')\')');
    }

    /**
     * @param  array
     *
     * @return \Illuminate\Support\Facades\DB
     */
    private function geometrySetLineString($value)
    {
        $tmp = [];
        foreach ($value as $point) {
            if (!$this->chechData($point)) {
                return null;
            }
            $tmp[] = implode(" ", $point);
        }
        $tmp = implode(',', $tmp);
        return DB::raw('GeomFromText(\'LINESTRING(' . $tmp . ')\')');
    }

    /**
     * @param  array
     *
     * @return \Illuminate\Support\Facades\DB
     */
    private function geometrySetPolygon($value)
    {
        $tmp = [];
        foreach ($value as $pointArray) {
            $tmpPoint = [];
            foreach ($pointArray as $point) {
                if (!$this->chechData($point)) {
                    return null;
                }
                $tmpPoint[] = implode(" ", $point);
            }
            $tmp[] = '(' . implode(',', $tmpPoint) . ')';
        }
        $tmp = implode(',', $tmp);
        return DB::raw('GeomFromText(\'POLYGON(' . $tmp . ')\')');
    }


    /**
     * @param  string $value  Point, line, surface, string expression
     *                        example:
     *                        --point   "GeomFromText('POINT(12 34)')"
     *                        --linestring  "GeomFromText('LINESTRING(12  34,45 56,56 78)')"
     *                        --polygon     "GeomFromText('POLYGON((0 0,10 0,10 10,0 10,0 0))"
     *
     * @return array|null
     */
    private function getPoints($value)
    {
        $pointArray = [];
        preg_match_all('/\(([0-9\. ,-]+)\)/', $value, $matchs);
        if (isset($matchs[1]) && !empty($matchs[1])) {
            foreach ($matchs[1] as $pointString) {
                $points = $this->parsePoint($pointString);
                if ($points == null) {
                    return null;
                }
                $pointArray[] = $points;
            }
        }
        return empty($pointArray) ? null : $pointArray;
    }

    /**
     * To determine whether the data is legal
     *
     * @param  array $array
     *
     * @return bool
     */
    private function chechData($array)
    {
        if (count($array) != 2) {
            return false;
        }
        foreach ($array as $val) {
            if (!is_float($val) && !is_int($val)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param  string $pointString   The coordinates of the points in the coordinates of the points.
     *                               Example: '1 2,3 4,5 6'
     *
     * @return array|null
     */
    private function parsePoint($pointString)
    {
        $pointString = trim($pointString);
        $pointArray = explode(',', $pointString);
        foreach ($pointArray as $key => $value) {
            $array = explode(' ', trim($value));
            if ($this->chechData($array)) {
                return null;
            }
            $pointArray[$key] = $array;
        }
        return $pointArray;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeAddSelectGeometryColumn($query)
    {
        if (!empty($this->geofields)) {
            $raw = ' *, ';
            $columns = array_keys($this->geofields);
            foreach ($columns as $column) {
                $raw .= ' astext(' . $column . ') as ' . $column . ' ,';
            }
            $raw = substr($raw, 0, -1);
            return $query->addSelect(DB::raw($raw));
        }
        return $query;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  int                                $dist     Distance to the current point
     * @param  array                              $location current point [lat,lng]
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithInDistance($query, $dist, $location, $field)
    {
        $query = $this->spatialBuilder($query, $field, $location[0], $location[1], $dist);
        return $query->whereRaw('GET_DISTANCE(X(location),Y(location),' . $location[0] . ',' . $location[1] . ') < ' . $dist);
    }

    private function replaceGeometryValue()
    {
        if (!empty($this->attributes) && !empty($this->geofields)) {
            foreach ($this->attributes as $key => $value) {
                if (array_key_exists($key, $this->geofields)) {
                    $this->attributes[$key] = $this->makeValueToSetting($value, $this->geofields[$key]);
                }
            }
        }
        return $this->attributes;
    }

    /**
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  array                                 $options
     *
     * @return bool
     */
    protected function performInsert(Builder $query, array $options = [])
    {
        $this->attributes = $this->replaceGeometryValue();
        return parent::performInsert($query, $options);
    }

    /**
     * make spatial condition by location
     *
     * @param string $field  find field name
     * @param float  $lat    latitude
     * @param float  $lon    longitude
     * @param int    $radius search longitude (unit km)
     *
     * @return string
     */
    private function makeSpatialCondition($field, $lat, $lon, $radius)
    {
        $deltaLat = $radius / 111;  //The spherical distance of a unit of latitude (111 km)
        $deltaLon = $radius / 82;   //The spherical distance of a unit of longitude (82 km)

        $points = [
            [($lat - $deltaLat), ($lon - $deltaLon)],
            [($lat - $deltaLat), ($lon + $deltaLon)],
            [($lat + $deltaLat), ($lon + $deltaLon)],
            [($lat + $deltaLat), ($lon - $deltaLon)],
        ];

        return sprintf('MBRWithin(%s,GeomFromText(\'%s\'))',
            $field,
            $this->makePolygon($points));
    }

    /**
     * Specified search area search
     *
     * @param array     $points Graphic point coordinate
     * @param bool|true $close
     *
     * @return string
     */
    private function makePolygon(array $points, $close = true)
    {
        if ($close) {
            $points[] = $points[0];
        }

        return 'Polygon((' . implode(',', array_map(function ($point) {
            return implode(' ', $point);
        }, $points)) . '))';
    }

    private function spatialBuilder($query, $field, $lat, $lon, $radius)
    {
        return $query->whereRaw($this->makeSpatialCondition($field, $lat, $lon, $radius));
    }
}
