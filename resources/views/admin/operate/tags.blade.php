@extends('admin.layout.main')

@section('title', '标签列表')

@section('stylesheet')

@endsection


@section('content')
    <div id="membersList" class="mt20">
        <div id="listCon">
            <div id="tableCon">
                <table>
                    <thead>
                        <tr>
                            <th>序号</th>
                            <th>标签名称</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript" src="/static/admin/js/operate/tags.js"></script>
@endsection
