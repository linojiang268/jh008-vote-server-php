@extends('admin.layout.main')

@section('title', '创建账号')

@section('stylesheet')

@endsection


@section('content')
    <form class="ui-form" name="" method="post" action="#" id="registerForm">
        <div class="ui-form-item">
            <label for="" class="ui-label">
                用户名:
            </label>
            <input name="user_name" id="user_name" class="form-control w200" type="text" placeholder="请输入用户名">
        </div>

        <div class="ui-form-item">
            <label for="" class="ui-label">
                密码:
            </label>
            <input name="password" id="password" class="form-control w200" type="password" placeholder="请输入密码">
        </div>

        <div class="ui-form-item">
            <label for="" class="ui-label">
                确认密码:
            </label>
            <input name="confirm_password" id="confirm_password" class="form-control w200" type="password" placeholder="请输入确认密码">
        </div>

        <div class="ui-form-item">
            <label for="" class="ui-label">
                角色:
            </label>
            <div class="ui-select ui-select-middel" id="roleSelect">
                <span class="ui-select-text">运营管理员</span>
                <span class="tri-down"></span>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="javascript:;" data-role="operator">运营管理员</a></li>
                    <li><a href="javascript:;" data-role="accountant">财务管理员</a></li>
                </ul>
            </div>
        </div>

        <div class="ui-form-item">
            <label for="" class="ui-label">
                &nbsp;
            </label>
            <div class="ui-submit-wrap">
                <input type="submit" class="ui-form-submit" value="创建账号">
            </div>
        </div>
    </form>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script type="text/javascript" src="/static/admin/js/user/accountCreate.js"></script>
@endsection
