$(function(){
        function addSpace(mobile){
            //13211112222
            var a = mobile.split('');
            a.splice(3, 0, '-');
            a.splice(8, 0 ,'-');
            var b = a;
            var c = b.join('').replace(/-/g,' ');
            //c: 132 1111 2222
            return c;
        }

        var $phone = $('#userPhoneNum'),
            m = 0,
            n;
        $phone.text(addSpace($phone.text()));

        // history.pushState({"page": "a1"}, "" , "?activity_id="+$('#aid'));
        // 监听返回按钮
        // window.addEventListener('popstate', function(e) {
        //     if ( !m ) {
        //         m += 1;
        //         return;
        //     }else {
        //         window.location.href = 'http://www.baidu.com';
        //     }
        // });
    });