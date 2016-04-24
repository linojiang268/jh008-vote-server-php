@extends('admin.layout.main')

@section('title', '个人设置-修改密码')

@section('stylesheet')

@endsection


@section('content')
    <form class="ui-form" name="" method="post" action="#" id="updatePasswordForm">
        <div class="ui-form-item">
            <label for="" class="ui-label">
                旧密码:
            </label>
            <input name="old_password" id="old_password" class="form-control w200" type="password" placeholder="请输入密码">
        </div>

        <div class="ui-form-item">
            <label for="" class="ui-label">
                新密码:
            </label>
            <input name="password" id="password" class="form-control w200" type="password" placeholder="请输入密码">
        </div>

        <div class="ui-form-item">
            <label for="" class="ui-label">
                确认新密码:
            </label>
            <input name="confirm_password" id="confirm_password" class="form-control w200" type="password" placeholder="请输入确认密码">
        </div>

        <div class="ui-form-item">
            <label for="" class="ui-label">
                &nbsp;
            </label>
            <div class="ui-submit-wrap">
                <input type="submit" class="ui-form-submit" value="确认修改">
            </div>
        </div>
    </form>
    {!! csrf_field() !!}
@endsection


@section('javascript')
    <script type="text/javascript" src="/static/admin/js/user/updatePassword.js"></script>
@endsection
