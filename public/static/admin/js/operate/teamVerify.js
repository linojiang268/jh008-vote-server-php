(function(){
    var util = K.util,
        DialogUi = K.dialogUi,
        server = K.server;

    function detailDialog(data) {
        var contentString = template('team_detail_template', data);
        DialogUi.open({
            type: 1,
            title: data.name + '社团详情',
            area: ['600px', '600px'],
            shadeClose: true,
            content: contentString
        })
    }

    var _token = $('input[name="_token"]').val();
    var TeamVerifyList = (function() {

        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index', 
                    'name',
                    function(data) {
                        console.log(data);
                        return data.is_created ? '创建' : '修改';
                    },
                    function(data){
                        return '<a href="javascript:;" id="detail" class="button button-orange button-m">查看详情</a>';
                    },
                    function(data){
                        return  '<a id="pass" href="javascript:;" class="button button-orange button-m mr10">通过</a>' +
                                '<a id="refuse" href="javascript:;" class="button button-orange button-m">拒绝</a>';
                    }
                ],
                source: function(o, PagTable, option) {
                    var parms = {};
                    parms.page = o.currentPage;
                    parms.size = 20;
                    server.teamRequests(parms, function(resp){
                        if (resp.code == 0) {
                            PagTable({totalPage: resp.pages, datas: resp.requests});
                        } else {
                            DialogUi.alert(resp.msg || '查询数据列表出错');
                        }
                    })
                },
                events: {
                    "click #detail" : "detailHandler",
                    "click #pass"  : "passHandler",
                    "click #refuse"   : "refuseHandler"
                },
                eventsHandler: {
                    detailHandler: function(e, row) {
                        detailDialog(row.data);
                    },
                    passHandler: function(e, row) {
                        DialogUi.textarea({
                            title: '审核',
                            text: '确定要通过该请求？',
                            callback: function(text, next) {
                                var serverMethod = null;
                                if (row.data.is_created) {
                                    serverMethod = server.teamApprove;
                                } else if (!row.data.is_created) {
                                    serverMethod = server.teamUpdateRequestApprove;
                                }

                                if (serverMethod == null) {
                                    DialogUi.alert('操作不支持');
                                    return;
                                }
                                var curDialog = DialogUi.loading('操作中...');
                                serverMethod({
                                    _token: _token,
                                    request: row.data.id,
                                    memo: text || ''
                                }, function(resp) {
                                    curDialog.close();
                                    if (resp.code == 0) {
                                        next();
                                        row.destroy();
                                        DialogUi.message('已通过该审核请求');
                                    } else {
                                        DialogUi.alert('审核失败');
                                    }
                                })
                            }
                        })
                    },
                    refuseHandler: function(e, row) {
                        DialogUi.textarea({
                            title: '审核',
                            text: '确定要拒绝该请求？',
                            callback: function(text, next) {
                                var serverMethod = null;
                                if (row.data.is_created) {
                                    serverMethod = server.teamReject;
                                } else if (!row.data.is_created) {
                                    serverMethod = server.teamUpdateRequestReject;
                                }

                                if (serverMethod == null) {
                                    DialogUi.alert('操作不支持');
                                    return;
                                }
                                var curDialog = DialogUi.loading('操作中...');
                                serverMethod({
                                    _token: _token,
                                    request: row.data.id,
                                    memo: text || ''
                                }, function(resp) {
                                    curDialog.close();
                                    if (resp.code == 0) {
                                        next();
                                        row.destroy();
                                        DialogUi.message('已拒绝该审核请求');
                                    } else {
                                        DialogUi.message(resp.message || '请求出错了！');
                                    }
                                })
                            }
                        })
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
            TeamVerifyList.render();
        }
    }

    page.initialize();

})()