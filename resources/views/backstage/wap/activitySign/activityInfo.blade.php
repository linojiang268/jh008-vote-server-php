@extends('layouts.wapMain')



@section('stylesheet')
    <link rel="stylesheet" href="/static/wap/css/components.css"/>
    <link rel="stylesheet" href="/static/wap/css/pageSign.css?201510141114"/>
@endsection

@section('content')

@if (!$errors->isEmpty())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@else
    <div class="section-bd">
        <div id="p1" class="content-page page activity-mobile-w page-active">
            <div class="inner-page t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div>
            <div class="inner-page shadow">
                <section class="ac-info">
                    <div class="section-ac-process">
                        <h3 class="a-title">活动流程 <i class="sign-symbol"></i></h3>
                        <div class="ac-process-w">
                            <div class="ac-process-items">
                                <div class="process-item">
                                @if ($activity['activity_plans'])
                                    @foreach ($activity['activity_plans'] as $key => $value)
                                    <dl>
                                        <dt><span class="date">{{$key}}</span></dt>
                                        @foreach ($value as $date => $time)
                                        <dd>
                                            <span>{{substr($time['start'], 0, 5)}}-{{substr($time['end'], 0, 5)}}</span><i>:</i><span>{{$time['content']}}</span>
                                        </dd>
                                        @endforeach
                                    </dl>
                                    @endforeach
                                @else
                                    <div class="no-ac-plan">抱歉，主办方尚未发布</div>
                                @endif
                                </div>
                            </div>
                            <span class="more-arrow hiding"><i>......</i>more&or;</span>
                        </div>
                    </div>
                    <div class="section-annex">
                        <h3 class="a-title">活动手册</h3>
                        <div class="annex-w">
                            <a href="#" class="annex-item item1">成员({{$activity['activity_members_count']}})</a>
                            <a href="#" class="annex-item item2">相册({{$activity['activity_album_count']}})</a>
                            <a href="#" class="annex-item item3">文件({{$activity['activity_file_count']}})</a>
                            <a href="#" class="annex-item item4">现场图示</a>
                        </div>
                    </div>
                    <div class="section-organizers">
                        <h3 class="a-title">主办方</h3>
                        <ul>
                            @if (!$activity['organizers'])
                                <li>{{$activity['team']['name']}}</li>
                            @else 
                                @foreach ($activity['organizers'] as $key )
                                <li>{{$key}}</li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </section> 
            </div>
            <div class="inner-page b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div>
            @include('backstage.wap.downloadBanner')
        </div>
    </div>
    {!! csrf_field() !!}
    @endsection
@endif

@section('javascript')
    <script src="/static/wap/js/pageSign.js"></script>
@endsection

