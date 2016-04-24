<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, minimal-ui" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="format-detection" content="telephone=no, email=no" />
    <meta http-equiv="pragma" content="no-cache"  />
    <meta http-equiv="Cache-Control" content="no-cache, must-revalidate" />
    @yield('meta')
    <link rel="stylesheet" href="/static/wap/css/mobile-reset.css"/>
    <link rel="stylesheet" href="/static/wap/css/common.css?201510191213"/>
    @yield('stylesheet')
</head>
<body>
    <div id="main" class="wrap-page">
        @yield('content')
    </div>
    @yield('footer')
<script src="/static/wap/js/zepto.min.js"></script>
<script src="/static/wap/js/common.js?201510191213"></script>
@yield('javascript')
</body>
</html>