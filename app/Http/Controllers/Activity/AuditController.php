<?php
namespace Jihe\Http\Controllers\Activity;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Activity\Controller;

class AuditController extends Controller
{
    public function listAll(Request $request)
    {
        if ( ! $this->checkAuth($request)) {
            return $this->redirectToLoginForm($request);
        }

        $data = [];

        return view("backstage.wap.{$request['templateSegment']}.auditList", $data);
    }
}
