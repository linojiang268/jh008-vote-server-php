<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <title> 集合啦 全心全意为社团服务！</title>
    <link rel="stylesheet" href="/static/css/common/reset.css" />
    <link rel="stylesheet" href="/static/css/common/base.css" />
    <link rel="stylesheet" href="/static/admin/css/common.css" />
    <link rel="stylesheet" href="/static/css/login/login.css" />
    <link rel="stylesheet" href="/static/admin/css/login.css" />
</head>
<body>

<div class="container">
  <div class="login">
    <h1 class="fn-clear">
      <span class="tip-name">零创后台管理系统</span>
    </h1>
    <span id="error-tip">
      @if (!$errors->isEmpty())
        <div class="alert alert-error">{{ $errors->first() }}</div>
      @endif
    </span>
    <form method="post" id="loginForm" action="/admin/login">
      <p class="user-wrap">
        <label class="left-text">用户名</label> <input class="infor-input"  type="text" id="username" name="username" placeholder="用户名" />
      </p>
      <p>
        <label class="left-text">密码</label> <input class="infor-input" type="password" id="password" name="password" placeholder="密码" />
      </p>
      <p class="sign-tip clearfix">
        <label class="remember-label">
          <input type="checkbox" name="remember" class="remember" id="remember" /><span class="remember-text">记住我</span>
        </label>
        {!! csrf_field() !!}
      </p>
      <p class="submit">
        <input type="submit" id="login" class="login-btn" value="登录">
      </p>
      <!-- <p class="sign-tip"><a class="reg-tip" href="">注册账号</a></p> -->
    </form>
  </div>
</div>
  

<script src="/static/plugins/jquery-1.7.1.min.js"></script>
<script src="/static/js/common/K.js"></script>
<script src="/static/admin/js/common/server.js"></script>
<script src="/static/plugins/jquery.validate.js"></script>
<script src="/static/admin/js/login/login.js"></script>   

</body>
</html>