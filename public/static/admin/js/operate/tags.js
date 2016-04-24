(function(){
    var util = K.util,
        DialogUi = K.dialogUi,
        server = K.server;

    var TagsList = (function() {

        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index', 
                    'name'
                ],
                paginate: false,
                source: function(o, PagTable, option) {
                    var parms = {};
                    parms.page = o.currentPage;
                    server.tags(parms, function(resp){
                        if (resp.code == 0) {
                                PagTable({totalPage: 1, datas: resp.tags});
                        } else {
                            alert(resp.msg || '查询数据列表出错');
                        }
                    })
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
            TagsList.render();
        }
    }

    page.initialize();

})()