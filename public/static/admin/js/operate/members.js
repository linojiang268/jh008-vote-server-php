
(function(){
    var util = K.util,
        DialogUi = K.dialogUi,
        server = K.server;

    var MembersList = (function() {

        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index', 
                    'mobile',
                    'name',
                    'end_time',
                    function(data){
                        return '<a href="javascript:;" id="detail" class="button button-orange button-m">查看详情</a>';
                    },
                    function(data){
                        return  '<a id="reTag" href="javascript:;" class="button button-orange button-m mr10">定义标签</a>';
                    }
                ],
                source: function(o, PagTable, option) {
                    var parms = {};
                    parms.page = o.currentPage;
                    //server.blacklistList(parms, function(resp){
                        var resp = {
                            code: 0,
                            datas: [
                            {name: 'zhangsan', status: 2, start_time: 's', end_time: 'ddd'}, {name: 'zhangsan', status: 2}],
                            pages: 12
                        }
                        if (resp.code == 0) {
                                PagTable({totalPage: resp.pages, datas: resp.datas});
                        } else {
                            alert(resp.msg || '查询数据列表出错');
                        }
                    //})
                },
                events: {
                    "click #detail"              : "detailHandler",
                    "click #reTag"                : "reTagHandler"
                },
                eventsHandler: {
                    detailHandler: function(e, row) {

                    },
                    reTagHandler: function(e, row) {

                    }
                }
            })
        }

        function _render() {
            var table = renderTablePag();
        }

        return {
            render: _render
        }
    })()

    var page = {
        initialize: function(){
            MembersList.render();
        }
    }

    page.initialize();

})()