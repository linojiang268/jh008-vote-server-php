<?php
namespace Jihe\Contracts\Repositories;

interface CityRepository
{
    /**
     * get list of available cities
     *
     * @return array  array of \Jihe\Entities\City
     */
    public function findAvailableCities();
    
    /**
     * find city by its id
     *
     * @param int $city   id of city
     * @return \Jihe\Entities\City|null
     */
    public function findCity($city);
}
