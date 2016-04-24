@extends('admin.layout.main')

@section('title', '账号列表')

@section('stylesheet')

@endsection


@section('content')
    <div id="membersList">
        <div id="listCon">
            <div id="tableCon">
                <table>
                    <thead>
                        <tr>
                            <th>编号</th>
                            <th>用户名</th>
                            <th>角色</th>
                            <th>操作</th>
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
    <script type="text/javascript" src="/static/admin/js/user/account.js"></script>
@endsection
