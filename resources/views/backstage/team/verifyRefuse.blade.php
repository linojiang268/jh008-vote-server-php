@extends('layouts.main')

@section('title', '成员审核 - 已拒绝')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@section('stylesheet')

@endsection

@section('content')
    <div class="examine-con">
        @include('layouts.memberManagerNav')
        <div id="membersList" class="mt20">
            <div id="tableCon">
                <table>
                    <thead>
                        <tr>
                            <th>序号</th>
                            <th>手机号</th>
                            <th>昵称</th>
                            <th>自定义信息</th>
                            <th>备注信息</th>
                            <th>拒绝理由</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script src="/static/js/team/verifyRefuse.js" ></script>
@endsection