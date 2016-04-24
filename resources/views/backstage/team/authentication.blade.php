@extends('layouts.main')

@section('title', '社团审核')

@section('stylesheet')
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@endsection

@section('content')
    @include('layouts.settingNav')
    
    <div class="p15">
        <ul class="ui-step mt10">
            <li class="ui-step-item @if ($status == 0) ui-step-item-active @endif">
                <span class="ui-step-num">1</span>
                <span class="ui-step-text">填写认证资料</span>
                <a href="javascript:;" class="ui-step-dir"><i class="icon iconfont"></i></a>
            </li>
            <li class="ui-step-item @if ($status == 1) ui-step-item-active @endif">
                <span class="ui-step-num">2</span>
                <span class="ui-step-text">认证审核中</span>
                <a href="javascript:;" class="ui-step-dir"><i class="icon iconfont"></i></a>   
            </li>
            <li class="ui-step-item @if ($status == 2) ui-step-item-active @endif">
                <span class="ui-step-num">3</span>
                <span class="ui-step-text">认证成功</span>
            </li>
        </ul>
        @if ($status == 0)
        <p class="tip2 mt20">请将证件原件、社团认证资料清晰拍照或彩色扫描后上传，图片文件后缀支持jpg、png、格式，上传图片大小建议在4M以下。</p>
        <p class="tip2">请您上传清晰、无污物、完整的证件原件照片或彩色扫描件。</p>
        <p class="tip1">社团认证资料: <span class="tip1-infor">认证资料包括证书、公章或社团真实性证明材料(可上传1-8张图片)</span></p>
        <div class="ui-uploadThumbs clearfix mt20 infors-list" id="inforsList">

        </div>
        <p class="tip1">社团身份证证件照:</p>
        <div class="ui-uploadThumbs clearfix mt20" id="cardsList">
<!--             <div class="ui-uploadThumb">
    <div class="ui-uploadThumb-link" href="javascript:;">
        <div class="ui-uploadThumb-wrap">
            <div class="ui-uploadThumb-upload">
                <span class="ui-uploadThumb-icon"><i class="icon iconfont"></i></span>
                <span class="ui-uploadThumb-text">正面照</span>                               
            </div>
        </div>
        <input type="file" class="upload-target" />
    </div>
</div>  
<div class="ui-uploadThumb">
    <div class="ui-uploadThumb-link" href="javascript:;">
        <div class="ui-uploadThumb-wrap">
            <div class="ui-uploadThumb-upload">
                <span class="ui-uploadThumb-icon"><i class="icon iconfont"></i></span>
                <span class="ui-uploadThumb-text">反面照</span>                               
            </div>
        </div>
        <input type="file" class="upload-target" />
    </div>
</div>  -->
        </div>
        <a href="javascript:;" id="submitAuth" class="button button-orange w80 mt30">提交认证</a>
        @elseif ($status == 1)
            <div class="mt30">
                <p class="tip1">社团认证资料: </p>
                <div class="ui-uploadThumbs clearfix mt20"> 
                @foreach ($businessCertificates as $itemData)
                    <div class="ui-uploadThumb ui-uploadThumb-has">
                        <div class="ui-uploadThumb-link" href="javascript:;">
                            <div class="ui-upload-img-wrap">
                                <img src="{{ $itemData->getCertificationUrl() }}" alt="" />
                            </div>             
                        </div>
                    </div>
                @endforeach
                </div>
                <p class="tip1">社团身份证证件照: </p>
                <div class="ui-uploadThumbs clearfix mt20">
                    @foreach ($cardFront as $itemData)
                        <div class="ui-uploadThumb ui-uploadThumb-has">
                            <div class="ui-uploadThumb-link" href="javascript:;">
                                <div class="ui-upload-img-wrap">
                                    <img src="{{ $itemData->getCertificationUrl() }}" alt="" />
                                </div>             
                            </div>
                        </div>
                    @endforeach
                    @foreach ($cardBack as $itemData)
                        <div class="ui-uploadThumb ui-uploadThumb-has">
                            <div class="ui-uploadThumb-link" href="javascript:;">
                                <div class="ui-upload-img-wrap">
                                    <img src="{{ $itemData->getCertificationUrl() }}" alt="" />
                                </div>             
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
        @elseif ($status == 2)
        <div class="mt30">
            <p class="tip1">您的社团已经认证成功！</p>
        </div>
        @endif
    </div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script>
        var status = '{{ $status }}';
    </script>
    <script src="/static/plugins/webuploader/webuploader.js"></script>
    <script type="text/javascript" src="/static/js/common/imageUpload.js"></script>
    <script type="text/javascript" src="/static/js/team/authentication.js"></script>
@endsection