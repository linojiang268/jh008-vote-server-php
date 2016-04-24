<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="renderer" content="webkit">
    <title>集合-便捷的社团管理工具</title>
    <link rel="shortcut icon" href="/static/images/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="/static/css/login/normalize.css"/>
    <!-- <link rel="stylesheet" href="/static/css/login/jquery.fullPage.css"/> -->
    <link rel="stylesheet" href="/static/css/login/index.css?201510121723"/>
</head>
<body>
<div class="header">
    <!-- <div class="tool-bar"><a href="http://d.jh008.com"></a></div> -->
    <div class="tool-bar"><a id='top-icon' href="#"></a></div>
    <div class="nav">
        <ul>
            <li class="on"><a data-pid="1" href="#">社团登陆</a></li>
            <li><a data-pid="2" href="#">产品介绍</a></li>
            <li><a data-pid="3" href="#">商务合作</a></li>
            <li><a data-pid="4" href="#">联系我们</a></li>
        </ul>
    </div>
</div>
<div id="fullpage">
    <div class="section page1 active">
        <div class="bg"><img src="/static/images/login/s1.jpg" alt=""/></div>
        <div class="content-w">
            <img class="tip" src="/static/images/login/tip.png" alt=""/>
            <div class="btn-group">
                <button class="login btn btn--green anchor-login">登&nbsp;&nbsp;&nbsp;录</button>
                <button class="regist btn btn--lou">注&nbsp;&nbsp;&nbsp;册</button>
            </div>
            <div class="aa hide">
                <div class="h">扫码注册 <i class="close">X</i></div>
                <div><img src="/static/images/login/ddd.png" alt=""/></div>
                <div class="c">
                    下载集合app注册账号
                </div>
            </div>
        </div>

        <div class="container hide">
            <div class="login-mask"></div>
          <div class="login">
            <span class="closer">×</span>
            <h1 class="fn-clear">
              <span class="tip-name">登录</span>
            </h1>
            <span id="error-tip"></span>
            <form method="post" id="loginForm">
              <p class="user-wrap">
                <label class="left-text">用户名</label> <input class="infor-input"  type="text" id="mobile" name="mobile" placeholder="手机号" />
              </p>
              <p>
                <label class="left-text">密码</label> <input class="infor-input" type="password" id="password" name="password" placeholder="密码" />
              </p>
              <p class="sign-tip clearfix">
                <label class="remember-label">
                  <input type="checkbox" class="remember" name="remember" id="remember" /><span class="remember-text">记住我</span>
                </label>
                {!! csrf_field() !!}
              </p>
              <p class="submit">
                <input type="submit" id="login" class="login-btn" value="登录">
              </p>
              <!-- <p class="sign-tip"><a class="reg-tip" href="">注册账号</a></p> -->
              <span>* 请使用集合App的帐号和密码登录。</span>
            </form>
          </div>
      </div>
    </div>
    <div class="section page2">
        <div class="bg-item">
            <img src="/static/images/login/i1.jpg" alt=""/>
            <div class="content-w">
                <img class="logo-s" src="/static/images/login/logo.png" alt=""/>
                <div class="btn-down-group">
                    <a class="android" title="Android版本" href="http://dev.file.jhla.com.cn/app/android/jihe.apk"></a>
                    <a class="ios" title="ios版本下载"  href="https://itunes.apple.com/cn/app/ji-he-zhao-huo-dong-jiao-peng/id935532535?l=en&mt=8"></a>
                </div>
            </div>
        </div>
        <div class="bg-item">
            <img src="/static/images/login/i2.jpg" alt=""/>
        </div> 
        <div class="bg-item">
            <img src="/static/images/login/i3.jpg" alt=""/>
        </div> 
        <div class="bg-item">
            <img src="/static/images/login/i4.jpg" alt=""/>
        </div>     
    </div>
    <div class="section page3">
        <div class="bg"><img src="/static/images/login/s2.jpg" alt=""/></div>
        <div class="content-w">
            <img src="/static/images/login/Business-cooperation.jpg" alt=""/>
            <div class="executives-w">
                <ul>
                    <li>联系人：袁先生</li>
                    <li>电&nbsp;&nbsp;话：17708020269</li>
                    <li>邮&nbsp;&nbsp;箱：yds@jh008.com</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="section page4">
        <div class="bg"><img src="/static/images/login/s2.jpg" alt=""/></div>
        <div class="content-w contact-w">
            <img class="map" src="/static/images/login/map.png" alt=""/>
            <div class="via">
                <ul>
                    <li>中国·成都市·高新区</li>
                    <li>地址：天府三街香年广场T2-3313</li>
                    <li>电话：400-876-1176</li>
                    <li>邮箱： admin@jh008.com</li>
                </ul>
            </div>
            <div class="all-qr-w">
                <img src="/static/images/login/all.jpg" alt="" class="all-qr"/>
            </div>
        </div>
    </div>
</div>

<script> 
    var basePath = "";
</script>  
<script src="/static/plugins/jquery-1.11.3.min.js"></script>
<script src="/static/js/common/K.js"></script>
<script src="/static/plugins/jquery.validate.js"></script>
<script src="/static/plugins/jquery-ui-1.11.2.custom/jquery-ui.min.js"></script>
<script src="/static/js/common/base.js"></script> 
<script src="/static/js/common/server.js"></script> 
<script src="/static/plugins/layer/layer.js"></script>
<!-- <script src="/static/plugins/jquery.fullPage.min.js"></script> -->
<script src="/static/js/login/jquery.scrollTo.min.js"></script>
<script src="/static/js/login/login.js?201510121723"></script> 
</body>
</html>