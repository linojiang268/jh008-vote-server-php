$(function() {

    var util     = K.util,
        DialogUi = K.dialogUi,
        server   = K.server,
        size     = K.size;
    
    var NoticeList = (function() {
        var table;
        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index', 
                    'created_at',
                    'content'
                ],
                source: function(o, PagTable, option) {
                    var parms = {};
                    parms.page = o.currentPage;
                    parms.size = size;
                    server.noticeList(parms, function(resp){
                        if (resp.code == 0) {
                            PagTable({totalPage: resp.pages, datas: resp.messages});
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
            NoticeList.render();
        }
    }

    page.initialize();

})