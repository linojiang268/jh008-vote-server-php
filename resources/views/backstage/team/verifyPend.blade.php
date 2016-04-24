@extends('layouts.main')

@section('title', '成员审核 - 待审核')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@endsection

@section('content')
    <div class="examine-con">
        @include('layouts.memberManagerNav')
        <div id="membersList" class="mt20">
            <!-- <p class="mt20 mb15">审核成员太多？批量导入白名单，加快审核速度<a href="/community/team/verify/whitelist" class="button button-b-blue ml15">导入白名单</a></p> -->
            <div id="listCon">
                <div id="tableCon">
                    <table>
                        <thead>
                            <tr>
                                <th>序号</th>
                                <th>手机号</th>
                                <th>昵称</th>
                                <th>自定义信息</th>
                                <th>审核信息</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script src="/static/js/team/verifyPend.js" ></script> 
@endsection