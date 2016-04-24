$(function(){
  //引入依赖
  var server = K.server,
      DialogUi = K.dialogUi;

  //请求活动信息
  var parms = {
    "activity": Number($("#aid").val())
  }
  var a = 0;
  server.getActivity(parms,function(resp){
    if (resp.code == 0) {
      
      if (!resp.activity.title) {
        DialogUi.alert("活动名称为空啊");
        return;
      }

      // var url = getUrlHost()+"/wap/activity/detail?activity_id="+parms.activity;
      // $('#share-url').val(url); //活动详情地址
      // $('#share-url1').val(url+'#page=p2');//活动报名地址

      $('.copy-btn').on('click', function(){
        var _this = this;
        copyUrl(_this);
      });

      //配置分享插件
      window._bd_share_config={
        "common":{
            "bdSnsKey":{},
            "bdText":resp.activity.title,
            "bdMini":"2",
            "bdMiniList":false,
            "bdPic":resp.activity.cover_url,
            "bdStyle":"0",
            "bdSize":"32",
            "bdUrl" : $('#share-url').val(),
            "onAfterClick": function(cmd){
                if (cmd == 'weixin') {        
                  var timer = setInterval(function(){
                    if($('.bd_weixin_popup_head')){
                       $('.bd_weixin_popup_head > span').text('用微信扫一扫分享至朋友圈');
                       $('.bd_weixin_popup_foot').text('').parent().height(255);
                       clearInterval(timer);
                    }
                  },100) 
                }
              }
            },
            "share":{},
            "image":{
                "viewList":["tsina","weixin","qzone","renren","sqq"],
                "viewText":"分享到：","viewSize":"16"
            },
            "selectShare":{
                "bdContainerClass":null,
                "bdSelectMiniList":["tsina","weixin","qzone","renren","sqq"]
            }
          };
            with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion='+~(-new Date()/36e5)];

    }else {
      DialogUi.alert(resp.message);
    }
  });
  
  function getUrlHost() {
    var host = window.location.host,
        protocol = window.location.protocol;
    var origin = protocol+'//'+host;
    return origin;
  }

  function copyUrl(el) {
    var url = $(el).siblings('input')[0];
    url.select();
    document.execCommand('Copy');//执行浏览器复制命令
    DialogUi.message('复制成功 O(∩_∩)O ');
  }
});
