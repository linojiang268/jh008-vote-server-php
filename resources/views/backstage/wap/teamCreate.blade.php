<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@if (empty($team)) 创建社团 @else 社团信息 @endif</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, minimal-ui" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="format-detection" content="telephone=no, email=no" />
    <meta http-equiv="pragma" content="no-cache"  />
    <meta http-equiv="Cache-Control" content="no-cache, must-revalidate" />
    <link rel="stylesheet" href="/static/wap/css/mobile-reset.css"/>
    <link rel="stylesheet" href="/static/wap/css/common.css"/>
    <link rel="stylesheet" href="/static/css/iconfont/iconfont.css"/>
    <link rel="stylesheet" href="/static/css/common/lc_ui.css"/>
    <link rel="stylesheet" href="/static/wap/css/createTeam.css?201510271548"/>
</head>
<body>

<div class="container">

@if (!$errors->isEmpty()) 
  {{ $errors->first()}}
@else
    @if (empty($team))
        <h1 class="create-team-title">创建社团</h1>
        <form action="" id="createTeamForm" method="POST">
            <div class="info-item">
                <input type="text" id="name" name="name" class="input-way1" placeholder="*  社团名称" >
            </div>
            <div class="info-item">
                <select name="city" id="city">
                    @if ($cities)
                        @foreach ($cities as $city)
                        <option value="{{ $city->getId() }}">{{ $city->getName() }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="info-item hide">
                <input type="file" id="teamLogoUrl" class="none" name="teamLogoUrl" />
                <span id="teamLogoUrlText" class="none"></span>
                <a href="javascript:;" onclick="document.createTeam.teamLogoUrl.click()" class="long-btn1">上传LOGO</a>
                <div class="logo-upload-w">
                    <div class="logo-upload-main">
                        <img id="logo-img" src="" alt="">
                    </div>
                    <div class="upload-progress-w hide">
                        <p id="progress-percent"></p>
                        <p>上传中请稍等...</p>
                    </div>
                    <div class="logo-upload-preview-w">
                        <span class="preview-close" id="preview-close">x</span>
                        <div class="preview-box">
                            <div class="preview-box-con">
                                <img src="" alt="">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="logo-upload-btn-group">
                    <a href="javascript:;" class="long-btn1">上传LOGO</a>
                    <button class="crop-btn">预览</button>
                </div>
            </div>
            <div class="info-item">
                <textarea name="introduction" class="textarea-way1" placeholder="*  简介,100字以内" ></textarea>
            </div>
            <p class="create-info">
                以下信息不公开显示，仅用于客服核对联系。
            </p>
            <div class="info-item">
                <input type="text" name="address" class="input-way1" placeholder="   地址" >
            </div>
            <div class="info-item">
                <input type="text" name="contact" class="input-way1" placeholder="*  联系人" >
            </div>
            <div class="info-item">
                <input type="tel" name="contactPhone" class="input-way1" placeholder="*  手机号或座机号（座机格式：028-85175989）" >
            </div>
            <div class="info-item">
                <input type="email" name="email" class="input-way1" placeholder="*  邮箱" >
            </div>
            <div class="info-item">
                <button href="javascript:;" class="long-btn1 create-team-btn" id="createTeamBtn">创建社团</button>
            </div>
        </form>
    
    <!-- 审核中的社团信息 -->
    @else 
        @if (!empty($requests))
            @foreach ($requests as $request)
                @if ($request->getStatus() == 0)
                    @if (empty($request->getTeam()))
                        <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">您的社团正在审核中，会在1-2个工作日内完成审核。</a></div>
                    @else
                        <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">您的社团修改正在审核中，会在1-2个工作日内完成审核。</a></div>
                    @endif
                @elseif ($request->getStatus() == 1)
                    @if (empty($request->getTeam()))
                        <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">您的社团已通过审核</a>
                        <a href="javascript:;" data-id="{{ $request->getId() }}" class="badge inspect"><i class="icon iconfont"></i></a></div>
                    @else
                        <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">您的社团修改已通过审核</a>
                        <a href="javascript:;" data-id="{{ $request->getId() }}" class="badge inspect"><i class="icon iconfont"></i></a></div>
                    @endif
                @elseif ($request->getStatus() == 2)
                    @if (empty($request->getTeam()))
                        <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">您的社团创建未通过审核</a>
                        <a href="javascript:;" data-id="{{ $request->getId() }}" class="badge inspect"><i class="icon iconfont"></i></a></div>
                    @else
                        <div class="ui-infor"><a href="javascript:;" class="ui-infor-link color_info">您的社团修改未通过审核</a>
                        <a href="javascript:;" data-id="{{ $request->getId() }}" class="badge inspect"><i class="icon iconfont"></i></a></div>
                    @endif
                @endif
            @endforeach
        @endif

        <h1 class="create-team-title btc1">社团信息</h1>
        <div class="ui-form wap-team-info" name="" method="post" action="#" id="assnForm">
            <div class="ui-form-item">
                <label for="" class="ui-label">社团名称：</label>
                <span class="ui-form-text"> {{ $team->getName() }}</span>
            </div>
             @if (!$logo_modified)
            <div class="ui-form-item">
                <label for="" class="ui-label">
                    团队LOGO：
                </label>
                <div class="ui-form-item-wrap">
                    <p class="logo-tip">您可以登录集合pc管理端修改logo。(<span class="c-red">www.jh008.com</span>)</p>
                </div>
                <span></span>
            </div>
            @endif
            <div class="ui-form-item">
                <label for="" class="ui-label">
                    城&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;市：
                </label>
                <span class="ui-form-text"> {{ $team->getCity()->getName() }} </span>
            </div>

            <div class="ui-form-item">
                <label for="" class="ui-label">
                    地&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;址：
                </label>
                <span class="ui-form-text"> {{ $team->getAddress() }} </span>
            </div>

            <div class="ui-form-item le1">
                <label for="" class="ui-label">
                    联&nbsp;系&nbsp; 人：
                </label>
                <span class="ui-form-text"> {{ $team->getContact() }} </span>
            </div>

            <div class="ui-form-item">
                <label for="" class="ui-label">
                    联系电话：
                </label>
                <span class="ui-form-text"> {{ $team->getContactPhone() }} </span>
            </div> 

            <div class="ui-form-item">
                <label for="" class="ui-label">
                    电子邮箱：
                </label>
                <span class="ui-form-text"> {{ $team->getEmail() }} </span>
            </div>

            <div class="ui-form-item">
                <label for="" class="ui-label">
                    团队宣言：
                </label>
                <div class="ui-form-item-wrap">
                    <span class="ui-form-text"> {{ $team->getIntroduction() }}</span>
                </div>
            </div>

            <p style="margin-top: 40px; color:red;">社团管理、创建活动、社团资讯、社团相册、数据导出等功能，请用电脑操作：访问www.jh008.com，用App帐号和密码登录。</p>      
        </div>
    @endif
@endif
{!! csrf_field() !!}
</div>
<script src="/static/wap/js/zepto.min.js"></script>
<script src="/static/wap/js/common.js"></script>
<script src="/static/wap/js/server.js"></script>
<script>
    $(function () {
        var server = K.ns('server');
        // var teamLogoUrl = document.getElementById('teamLogoUrl'),
        //         teamLogoUrlText = document.getElementById('teamLogoUrlText');
        // teamLogoUrl.onchange = function(e) {
        //     var value = e.target.value;
        //     if (!value) return;
        //     teamLogoUrlText.innerHTML = value;
        //     teamLogoUrlText.style.display = 'block';
        // };

        $('#createTeamBtn').click(function () {
            var form = document.getElementById('createTeamForm');
            var name = $.trim($('#name', form).val());
            if (!validate(name, [{message: '请填写社团名', fn: isRequired}])) {
                $('#name', form).val(name).focus();
                return false;
            }

            var city = $('#city', form).val();
            if (!validate(city, [{message:'请选择城市', fn:isRequired}])) {
                $('#city', form).focus();
                return false;
            }

            var address = $('[name="address"]', form).val();
            // if (!validate(address, [{message:'请填写社团地址', fn:isRequired}])) {
            //     $('[name="address"]', form).val(address).focus();
            //     return false;
            // }

            var contact = $('[name="contact"]', form).val();
            if (!validate(contact, [{message:'请填写联系人', fn:isRequired}])) {
                $('[name="contact"]', form).val(contact).focus();
                return false;
            }

            var contactPhone = $('[name="contactPhone"]', form).val();
            if (!validate(contactPhone, [
                        {message: '请填写联系电话', fn: isRequired},
                        {message: '请填写正确的联系电话(手机或座机号)', fn: isContactPhone}])) {
                $('[name="contactPhone"]', form).val(contactPhone).focus();
                return false;
            }

            var email = $('[name="email"]', form).val();
            if (!validate(email, [
                        {message: '请填写邮箱', fn: isRequired},
                        {message: '请填写正确的邮箱', fn: isEmail}])) {
                $('[name="email"]', form).val(email).focus();
                return false;
            }

            var introduction = $('[name="introduction"]', form).val();
            if (!validate(introduction, [
                        {message: '请填写社团简介', fn: isRequired},
                        {message: '社团简介必须100字以内', fn: maxLength(100)}
                    ])) {
                $('[name="introduction"]', form).val(introduction).focus();
                return false;
            }

            // submit the request
            var params = {
                city: city,
                name: name,
                email: email,
                //logo_id: logo_id
                address: address,
                contact_phone: contactPhone,
                contact: contact,
                introduction: introduction,
                _token : $('input[name="_token"]').val(),
            };
            server.createTeam(params, function (response) {
                if (response.code == 0) {
                    alert("创建社团成功");
                    window.location.reload();
                } else {
                    alert(response.message || "创建社团失败");
                }
            });
            return false;
        });
        
        $('.inspect').bind('click', function(e) {
            var id = $(this).attr('data-id'),
                _this = this;
            server.inspect({request: id, _token: $('input[name="_token"]').val()}, function(resp) {
              if (resp.code == 0) {
                $(_this).parent('.ui-infor').remove();
              } else {
                alert(resp.messages || '服务器出错了');
              }
            })
        });

        function validate(value, rules) {
            for (var i = 0, n = rules.length; i < n; i++) {
                var rule = rules[i];
                if (!rule.fn(value)) {
                    if (rule.callback) {
                        rule.callback(false);
                    } else if (rule.message) {
                        alert(rule.message);
                    }

                    return false;
                }
            }

            return true;
        }

        function isRequired(value) {
            return value != '';
        }

        function isContactPhone(value) {
            return K.isTel(value) || K.isMobile(value);
        }

        function isEmail(value) {
            return /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+$/.test(value);
        }

        function maxLength(max) {
            return function (value) {
                return value.length <= max;
            }
        }
    });
</script>

</body>
</html>