@extends('admin.layout.main')

@section('title', '消息推送')

@section('stylesheet')

@endsection


@section('content')
        <div class="ui-tab2">
            <ul class="ui-tab2-items">
                <li class="ui-tab2-item ui-tab2-item-current">
                    <a href="/admin/notices">发送通知</a>
                </li>
                <li class="ui-tab2-item">
                    <a href="/admin/notices/list">通知记录</a>
                </li>            
            </ul>
        </div>
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
                        <span class="ui-form-required">&nbsp;</span>推送对象:
                    </label>
                    <div class="ui-submit-wrap">
                        <div class="ui-form-item-text">
                            所有人
                        </div>
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
    <script type="text/javascript" src="/static/admin/js/operate/notices.js"></script>
@endsection
