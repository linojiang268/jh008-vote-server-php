<?php

use Illuminate\Database\Seeder;
use Jihe\Models\UserTag;
use Jihe\Entities\UserTag as UserTagEntity;

class UserTagTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tags = [
            [1, 'sports'],
            [2, 'culture & art'],
            [3, 'photo & travel'],
            [4, 'music & dance'],
            [5, 'friends & party'],
            [6, 'reading & writing'],
            [7, 'foods'],
            [8, 'health'],
            [9, 'startup'],
            [10, 'help'],
            [11, 'children & parents'],
            [12, 'others'],
        ];

        foreach ($tags as $item) {
            list($id, $name) = $item;
            DB::table('user_tags')->insert([
                'id'           => $id,
                'name'         => $name,
                'resource_url' => 'default/tag_icon.jpg',
            ]);
        }
    }
}
