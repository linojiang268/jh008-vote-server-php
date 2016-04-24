@extends('admin.layout.main')

@section('title', '系统消息推送')

@section('stylesheet')

@endsection


@section('content')
        <div class="mt20">
            <form class="ui-form" name="" method="post" action="#" id="noticeForm">
                <div class="ui-form-item">
                    <label for="" class="ui-label">
                        <span class="ui-form-required">&nbsp;</span>推送内容:
                    </label>
                    <div class="ui-form-item-wrap">
                        <div class="w300">
                            <div class="limitText" id="limitText">
                                <div class="textarea-con">
                                    <textarea id="pushContent" name="content" class="limitText-ta" placeholder="可输入60字"></textarea>
                                    <span class="text-tip">0/60</span>                      
                                </div>
                            </div>                      
                        </div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">
                        <span class="ui-form-required">&nbsp;</span>平台:
                    </label>
                    <div class="ui-submit-wrap">
                        <div class="radioSels clearfix">
                            <div class="radio-wrap sel" id="androidPlate">
                                <input type="radio" checked="true" name="platform" value="android">
                                <label><i class="icon iconfont"></i>Android</label>
                            </div>
                            <div class="radio-wrap">
                                <input type="radio" name="platform" value="ios">
                                <label><i class="icon iconfont"></i>Ios</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">
                        <span class="ui-form-required">&nbsp;</span>强制更新:
                    </label>
                    <div class="ui-submit-wrap">
                        <div class="radioSels clearfix">
                            <div class="radio-wrap sel" id="compulsoryFalse">
                                <input type="radio" checked="true" name="compulsory" value=0>
                                <label><i class="icon iconfont"></i>否</label>
                            </div>
                            <div class="radio-wrap">
                                <input type="radio" name="compulsory" value=1>
                                <label><i class="icon iconfont"></i>是</label>
                            </div>
                        </div>
                    </div>
                </div>                
                <div class="ui-form-item">
                    <label for="" class="ui-label">
                        <span class="ui-form-required">&nbsp;</span>版本号:
                    </label>
                    <div class="ui-submit-wrap">
                        <input name="version" id="version" class="form-control w200" type="text" placeholder="请输入版本号">
                        <span class="ui-form-item-text">(必填)</span>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">
                        <span class="ui-form-required">&nbsp;</span>url:
                    </label>
                    <div class="ui-submit-wrap">
                        <input name="url" id="url" class="form-control w200" type="text" placeholder="请输入url">
                        <span class="ui-form-item-text">(选填)</span>
                    </div>
                </div>
                <div class="ui-form-item mt30">
                    <label for="" class="ui-label">
                        &nbsp;
                    </label>
                    <div class="ui-submit-wrap">
                        <input type="submit" id="pushBtn" class="ui-form-submit" value="推送消息">
                    </div>
                </div>
            </form>           
        </div>
        {!! csrf_field() !!}
@endsection


@section('javascript')
    <script type="text/javascript" src="/static/admin/js/operate/systemNotices.js"></script>
@endsection
