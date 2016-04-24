<?php
namespace Jihe\Http\Controllers\Admin;

use Jihe\Http\Controllers\Controller;
use Jihe\Services\TagService;
use Jihe\Entities\UserTag;

class HomeController extends Controller
{
    public function login()
    {
        return view('admin.home.login');
    }

    public function updatePass()
    {
        return view('admin.home.updatePass');
    }         

    public function listTag(TagService $tagService)
    {
        $tags = $tagService->listTags();
        return $this->json([
            'tags' => array_map([$this, 'tagToArray'], $tags),
        ]);
    }

    private function tagToArray(UserTag $userTag)
    {
        return [
            'id'    => $userTag->getId(),
            'name'  => $userTag->getName(),
            'url'   => $userTag->getResourceUrl(),
        ];
    }
}
