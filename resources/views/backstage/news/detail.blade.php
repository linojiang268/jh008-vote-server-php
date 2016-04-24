<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <title>活动资讯详情</title>
    <link rel="stylesheet" href="/static/css/common/reset.css"/>
    <link rel="stylesheet" href="/static/css/common/base.css"/>

    <style stylesheet="text/css">
        html,body { height:100%; } 
        body { background: url("/static/images/activity/bg.jpg") center; } 
        .section-bd { height: 100%;}
        .section-bd { width: 960px; margin: 0 auto; background: #fff; overflow: hidden;}
        .section-banner img { width: 100%; height: 400px; }
        .section-content { padding: 10px 10px;}
        .section-content img {max-width: 940px;}
        .hide {display: none;}
        @media screen and (max-width: 640px) {
            body { background: #ccc; } 
            .section-bd { width: 100%; margin: 0 auto; background: #fff; }
            .section-banner img {   width: 96%; height: 10%; margin: 2% 2%; }
            .section-content p { padding: 0;}
            .section-content {padding: 2%;}
            .section-content img { width: 100%;}
        }
    </style>
</head>
<body>
    <div class="section-bd">
        <div class="section-banner"><img id="banner" src="/static/images/activity/5.jpg" alt=""></div>
        <div class="section-content" id="content"></div>
    </div>
    <p id='hide-content' class="hide">{{ $news->getContent() }}</p>
</body>
<script>
    var con = document.getElementById('hide-content');
    var p = document.getElementById('content');
    p.innerHTML = con.innerText || con.textContent;

    var index = Math.floor(Math.random()*10),
        banner = document.getElementById('banner');
    if ( index >5 ) {
        index = 5;
    }
    banner.src = "/static/images/activity/"+index+".jpg";
</script>
</html>
