@extends('layouts.main')

@section('title', '社团信息')

@section('stylesheet')
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link rel="stylesheet" href="/static/css/team/assn.css?201510141243"/>
    <link rel="stylesheet" href="/static/plugins/jcrop-master/css/jquery.Jcrop.css"/>
@endsection

@section('content')
    @include('layouts.settingNav')

    @if (!empty($requests))
        @foreach ($requests as $request)
            @if ($request->getStatus() == 0)
                @if (empty($request->getTeam()))
                    <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">{{$request->getUpdatedAt()}} 您的社团正在审核中，会在1-2个工作日内完成审核。</a></div>
                @else
                    <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">{{$request->getUpdatedAt()}} 您的社团修改正在审核中，会在1-2个工作日内完成审核。</a></div>
                @endif
            @elseif ($request->getStatus() == 1)
                @if (empty($request->getTeam()))
                    <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">{{$request->getUpdatedAt()}} 您的社团已通过审核</a>
                    <a href="javascript:;" data-id="{{ $request->getId() }}" class="badge inspect"><i class="icon iconfont"></i></a></div>
                @else
                    <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">{{$request->getUpdatedAt()}} 您的社团修改已通过审核</a>
                    <a href="javascript:;" data-id="{{ $request->getId() }}" class="badge inspect"><i class="icon iconfont"></i></a></div>
                @endif
            @elseif ($request->getStatus() == 2)
                @if (empty($request->getTeam()))
                    <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">{{$request->getUpdatedAt()}} 您的社团创建未通过审核</a>
                    <a href="javascript:;" data-id="{{ $request->getId() }}" class="badge inspect"><i class="icon iconfont"></i></a></div>
                @else
                    <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">{{$request->getUpdatedAt()}} 您的社团修改未通过审核</a>
                    <a href="javascript:;" data-id="{{ $request->getId() }}" class="badge inspect"><i class="icon iconfont"></i></a></div>
                @endif
            @endif
        @endforeach
    @endif

    @if (empty($team) || $updateStatus)
        @include('backstage.team.profileEdit')
    @else
        @include('backstage.team.profileShow')
    @endif

    @if (empty($created_team))
        <script>
            var mode = 'create';
            @if (!empty($team)) 
                var _status = true;
            @else 
                var _status = false;
            @endif
        </script>
    @else
        <script>
            var mode = 'update';
        </script>
    @endif 

    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script src="/static/plugins/webuploader/webuploader.js"></script>
    <script src="/static/plugins/jcrop-master/js/jquery.Jcrop.min.js"></script>
    <script src="/static/js/team/profile.js?201510101956"></script>
@endsection
