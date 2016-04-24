<?php
namespace intg\Jihe\Controllers\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Entities\UserTag;

class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    //=========================================
    //                 listTag
    //=========================================
    public function testListTagSuccessfully()
    {
        factory(\Jihe\Models\UserTag::class)->create([
            'id'    => 1,
            'name'  => 'sports',
        ]);
        factory(\Jihe\Models\UserTag::class)->create([
            'id'    => 2,
            'name'  => 'culture & art',
        ]);

        $this->startSession();
        $this->ajaxGet('admin/tag/list')
             ->seeJsonContains([
                'code'  => 0,
            ]);

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('tags', $response);
        $this->assertCount(2, $response->tags);
        $this->assertEquals(1, $response->tags[0]->id);
        $this->assertEquals('sports', $response->tags[0]->name);
    }
}
