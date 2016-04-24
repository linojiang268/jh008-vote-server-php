<!doctype>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="renderer" content="webkit">
        <title>2016武侯区单身联谊会</title>
        <link href="//cdn.bootcss.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
    <div class="container">
        <h1 class="text-center">2016武侯区单身联谊会后台审核系统</h1>

        <div class="row" style="margin-top: 40px;">
            <div class="col-md-3">&nbsp;</div>
            <div class="col-md-6">
                <form method="post" action="/act/xinnian/login">
                    <div class="form-group">
                        <label for="exampleInputEmail1">用户名</label>
                        <input type="text" class="form-control" name="username">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputPassword1">密码</label>
                        <input type="password" class="form-control" name="password">
                    </div>

                    <button type="submit" class="btn btn-primary">登录</button>
                </form>
                @if (count($errors) > 0)
                    <div class="alert alert-danger" style="color:red;">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div class="col-md-3">&nbsp;</div>
        </div>
    </div>
    </body>
</html>
