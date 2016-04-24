    @extends('layouts.main')

@section('title', '加入条件')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@endsection

@section('content')
    @include('layouts.settingNav')
    <div class="condition-con">
        <div class="p15">
            @if ($joinCondition)
            <div class="radioSels clearfix">
                <div>
                    @if ($joinCondition['joinType'] == 0)
                    <div class="radio-wrap sel">
                        <input type="radio" checked="true" name="condition" value="0">
                        <label><i class="icon iconfont"></i>无需审核</label>
                    </div>
                    @elseif ($joinCondition['joinType'] == 1)
                    <div class="radio-wrap">
                        <input type="radio" name="condition" value="0">
                        <label><i class="icon iconfont"></i>无需审核</label>
                    </div>
                    @endif
                </div>
                <div>
                    @if ($joinCondition['joinType'] == 0)
                    <div class="radio-wrap">
                        <input type="radio" name="condition" value="1">
                        <label><i class="icon iconfont"></i>需审核</label>
                    </div>
                    @elseif ($joinCondition['joinType'] == 1)
                    <div class="radio-wrap sel">
                        <input type="radio" checked="true" name="condition" value="1">
                        <label><i class="icon iconfont"></i>需审核</label>
                    </div>
                    @endif
                    <div class="verify-con @if ($joinCondition['joinType'] == 0) none @endif">
                        <!-- <div class="checkboxSels clearfix">
                            <div class="checkbox-wrap" d-select="on">
                                <input type="checkbox"  value="1" checked="true">
                                <label><i class="icon iconfont"></i>手机号（既注册帐号，默认选中）</label>
                            </div>
                            <div class="checkbox-wrap" d-select="on">
                                <input type="checkbox" name="bbb" value="2">
                                <label><i class="icon iconfont"></i>填加审核条件</label>
                            </div>
                        </div> -->

                        <div id="enrollAttrsCon" class="enroll-attrs-con clearfix">
                            <div class="enroll-attrs-wrap">
                                <p class="tip1">社团加入条件设置：</p>
                                <div id="enrollAttrs" class="enroll-attrs">
                                    <div class="ui-infor ui-attr-item" data-id="1">
                                        <a href="javascript:;" attr-id="-1" class="ui-infor-link">手机号</a>
                                    </div>
                                    @if ($joinCondition['requirements'])
                                        @foreach ($joinCondition['requirements'] as $requirement)
                                            <div class="ui-attr-item">
                                                <input type="text" data-id="{{ $requirement->getId() }}" value="{{ $requirement->getRequirement() }}" class="form-control ui-infor-link" placeholder="请输入报名条件">
                                                <a href="javascript:;" class="ui-attr-item-del"><i class="icon iconfont"></i></a>
                                            </div>
                                        @endforeach
                                    @endif                                     
                                </div>
                                <a href="javascript:;" id="addAttrsBtn" class="button button-lg button-lg-pre button-orange add-attr-btn"><i class="icon iconfont"></i>添加新字段</a>
                            </div>
                            <div id="preveiwMobile" class="preview-mobile">
                                
                            </div>
                        </div>

                    </div>
                </div>
                <div class="cd-sure-w">
                    <a href="javascript:;" id="sureBtn" class="button button-orange">确定修改</a>
                </div>
            </div>
            @endif
        </div>        
    </div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script type="text/javascript" src="/static/js/team/condition.js"></script>
@endsection