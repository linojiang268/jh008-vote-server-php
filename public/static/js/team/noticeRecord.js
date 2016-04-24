$(function() {

    var util     = K.util,
        DialogUi = K.dialogUi,
        server   = K.server,
        size     = K.size;

   /* function initTabs() {
        var tabs = lc.tabs($('#tabs'));
        tabs.on('switch', function(role) {
            NoticeList.render(role);
        })
        tabs.select('all');
    }*/
    
    var NoticeList = (function() {
        var table;
        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index', 
                    'created_at',
                    'content'
                    /*function(data){
                        return  '<a id="detail" href="javascript:;" class="button button-orange button-m">详情</a>';
                    }*/
                ],
                source: function(o, PagTable, option) {
                    var parms = {};
                    parms.page = o.currentPage;
                    parms.size = size;
                    server.teamNoticeList(parms, function(resp){
                        /*var resp = {
                            code: 0,
                            body: {
                                members: [{id:1,    name: '12345678',time: '2012/12/12 12:00:00',username: '张三',group: 'A',infro: 'app系统消息'},
                                {id:2,  name: '12345678',time: '2012/12/12 12:00:00',username: '张三',group: 'A',infro: 'app系统消息'},
                                {id:3,  name: '12345678',time: '2012/12/12 12:00:00',username: '张三',group: 'A',infro: 'app系统消息'},
                                {id:4,  name: '12345678',time: '2012/12/12 12:00:00',username: '张三',group: 'A',infro: 'app系统消息'},
                                {id:5,  name: '12345678',time: '2012/12/12 12:00:00',username: '张三',group: 'A',infro: 'app系统消息'}],
                                total_num: 20
                            }
                        };*/
                        if (resp.code == 0) {
                            if (resp.messages.length) {
                                PagTable({totalPage: resp.pages, datas: resp.messages});
                            }
                        } else {
                            alert(resp.msg || '查询数据列表出错');
                        }
                    })
                },
                perNums: size
            })
        }

        function _render(role) {
            if (!table) {
                table = renderTablePag();
            } else {

            }
        }

        return {
            render: _render
        }
    })()



    var page = {
        initialize: function() {
            // initTabs();
            NoticeList.render();
        }
    }

    page.initialize();

})