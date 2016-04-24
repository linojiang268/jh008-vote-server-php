<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="renderer" content="webkit">
        <title>集合 - 校园电商平面模特大赛</title>
    </head>
        <div>
            <h2>校园电商平面模特大赛</h2>
        </div>
        <div>
        <form method="post" action="/act/planemodel/login">
            <label>用户名: </label><input type="text" name="username" value="" />
            <label>密  码: </label><input type="password" name="password" value="" />
            <input type="submit" value="登录" />
        </form>
        </div>
        @if (count($errors) > 0)
        <div class="alert alert-danger" style="color:red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    <body>
    </body>
</html>
