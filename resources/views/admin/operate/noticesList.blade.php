@extends('admin.layout.main')

@section('title', '消息记录')

@section('stylesheet')

@endsection


@section('content')
        <div class="ui-tab2">
            <ul class="ui-tab2-items">
                <li class="ui-tab2-item">
                    <a href="/admin/notices">发送通知</a>
                </li>
                <li class="ui-tab2-item ui-tab2-item-current">
                    <a href="/admin/notices/list">通知记录</a>
                </li>            
            </ul>
        </div>
        <div class="mt10">
            <div id="tableCon">
                <table>
                    <thead>
                        <tr>
                            <th>序号</th>
                            <th>发送时间</th>
                            <th>发送内容</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>  
        </div>
@endsection


@section('javascript')
    <script type="text/javascript" src="/static/admin/js/operate/noticesList.js"></script>
@endsection
