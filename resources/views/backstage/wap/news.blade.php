@extends('layouts.wapMain')

@section('title') 
{{ $news->getTitle() }}
@endsection

@section('stylesheet')
    <link rel="stylesheet" href="/static/wap/css/news.css">
@endsection

@section('content')
    @if (!$errors->isEmpty())
    <section class="team-infor clearfix" style="height:310px;">
         <div class="alert alert-error" style="font-weight:bolder; font-size: 20px; text-align:center; padding-top: 50px;">{{ $errors->first() }}</div>
    </section>
    @else
    <section class="section-bd">
        <h1 class="news-title">{{ $news->getTitle() }}</h1>
        <div class="news-time">
            <span>{{ $news->getTeam()->getName() }}</span>
            发表于
            <span>{{ $news->getPublishTime() }}</span>
        </div>
        <div class="section-content" id="content"></div>
    </section>
    @endif
    <p id='hide-content' class="hide">
       {{ $news->getContent() }}
    </p>

@endsection

@section('javascript')
    <script type="text/javascript">
        var con = document.getElementById('hide-content');
        var p = document.getElementById('content');
        p.innerHTML = con.innerText || con.textContent;

        var appId = '{{ $sign_package["appId"] }}',
            timestamp = '{{ $sign_package["timestamp"] }}',
            nonceStr = '{{ $sign_package["nonceStr"] }}',
            signature = '{{ $sign_package["signature"] }}';

        var actTitle = '{{ $news->getTitle() }}',
            actLink  = location.href,
            actDesc  = '{{ $team->getName() }}',
            actImg   = '{{ $news->getCoverUrl() }}';

        wx.config({
           // debug: true, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
            appId: appId, // 必填，公众号的唯一标识
            timestamp: timestamp, // 必填，生成签名的时间戳
            nonceStr: nonceStr, // 必填，生成签名的随机串
            signature: signature,// 必填，签名，见附录1
            jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
        });


        wx.ready(function() {
            wx.onMenuShareTimeline({
                title: actTitle, // 分享标题
                link: actLink, // 分享链接
                imgUrl: actImg, // 分享图标
                success: function () { 
                    // 用户确认分享后执行的回调函数
                },
                cancel: function () { 
                    // 用户取消分享后执行的回调函数
                }
            });  

            wx.onMenuShareAppMessage({
                title: actTitle, // 分享标题
                desc: actDesc, // 分享描述
                link: actLink, // 分享链接
                imgUrl: actImg, // 分享图标
                type: '', // 分享类型,music、video或link，不填默认为link
                dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
                success: function () { 
                    // 用户确认分享后执行的回调函数
                },
                cancel: function () { 
                    // 用户取消分享后执行的回调函数
                }
            });

        })

    </script>
@endsection