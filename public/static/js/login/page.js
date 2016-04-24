/**
 * Created by Administrator on 2015/8/20.
 */
$(function(){
    //pageinit
    (function(){
        function initHeight(){
            var wHeight = $(window).height();
            $('.page3 .bg,.page4 .bg').children('img').height(wHeight);
        }
        $(window).resize(function () {
            initHeight();
        });
        initHeight();
    })();
    //$('#fullpage').fullpage({
    //    controlArrows: true,
    //    sectionsColor: ['#aaa0ff', '#4BBFC3', '#7BAABE', 'olive'],
    //    //anchors: ['page1', 'page2', 'page3', 'page4'],
    //    'navigation': true,
    //    'navigationPosition': 'right',
    //    'navigationTooltips': ['社团登陆', '产品介绍', '商务合作', '联系我们'],
    //    scrollOverflow: true
    //});

    $('.nav').on('click','li', function (e) {
        var index = $(this).index();
        var pid = $(this).children('a').data('pid');
        $.scrollTo('.page'+pid,1000);
        //var nav_anchors = $('#fp-nav');
        //nav_anchors.find('li').eq(index).children('a').click();
        $(this).addClass('on').siblings().removeClass('on');
        return false;
    });

    $('.close').click(function () {
        $('.aa').hide();
        $('.tip').show();
    });
    $('.regist').click(function(){
        $('.aa').show();
        $('.tip').hide();
    });
    //fp-nav
});