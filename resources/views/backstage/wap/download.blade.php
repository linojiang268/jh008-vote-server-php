@extends('layouts.wapMain')

@section('title', '社团主页')

@section('stylesheet')
    <link rel="stylesheet" href="/static/wap/css/download.css">
@endsection

@section('content')
    <div class="download-page">
        <div class="inner-page t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div> 
        <div class="inner-page dl-content shadow">
            <div class="dl-logo-w dl-w"><img src="/static/wap/images/ICON_2x.png" alt=""></div>
            <div class="dl-name-w dl-w"><img src="/static/wap/images/logoh5.png" alt=""></div>
            <!-- <div class="dl-find-w dl-w"><img src="/static/wap/images/finda_2x.png" alt=""></div> -->
            <a class="dl-android-link" href="http://dev.file.jhla.com.cn/app/android/jihe.apk"></a>
            <a class="dl-iphone-link" href="https://itunes.apple.com/cn/app/ji-he-zhao-huo-dong-jiao-peng/id935532535?l=en&mt=8"></a>
                  
        </div>
        <div class="inner-page  b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div> 
    </div>
@endsection


@section('javascript')

@endsection