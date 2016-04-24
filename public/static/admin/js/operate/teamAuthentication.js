(function(){
    var util = K.util,
        DialogUi = K.dialogUi,
        server = K.server,
        size = K.LIST_SIZE;

    var _token = $('input[name="_token"]').val();

    var certsManager = (function() {
        var cache = {};
        function _get(teamId, deferred) {
            if (!cache[teamId]) {
                var dialog = DialogUi.loading('正在请求中...');
                server.teamCertificationDetail({
                    team: teamId
                }, function(resp) {
                    dialog.close();
                    if (resp.code == 0) {
                        delete resp.code;
                        cache[teamId] = resp;
                        deferred.resolve(resp);
                    } else {
                        DialogUi.alert(resp.message || '获取认证资料出错了！');
                    }
                })
            } else {
                deferred.resolve(cache[teamId]);
            }
        }

        return {
            get: _get
        }
    })();

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

    function authenticationDialog(name, data) {
        var contentString = template('authentication_detail_template', data);
        DialogUi.open({
            type: 1,
            title: name + '社团认证详情',
            area: ['600px', '600px'],
            shadeClose: true,
            content: contentString
        })
    }

    var TeamAuthenticationList = (function() {

        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index', 
                    'name',
                    function(data) {
                        return '<a href="javascript:;" id="authentiationDetail" class="button button-orange button-m">查看</a>';
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
                    parms.size = size;
                    server.teamCertification(parms, function(resp){
                        if (resp.code == 0) {
                            PagTable({totalPage: resp.pages, datas: resp.teams});
                        } else {
                            DialogUi.alert(resp.msg || '查询数据列表出错');
                        }
                    })
                },
                perNums: size,
                events: {
                    "click #authentiationDetail" : "authentiationDetailHandler",
                    "click #detail"              : "detailHandler",
                    "click #pass"                : "passHandler",
                    "click #refuse"              : "refuseHandler"
                },
                eventsHandler: {
                    authentiationDetailHandler: function(e, row) {
                        var deferred = $.Deferred();
                        certsManager.get(row.data.id, deferred);
                        deferred.then(function(resp) {
                            authenticationDialog(row.data.name, resp);
                        })
                    },
                    detailHandler: function(e, row) {
                        detailDialog(row.data);
                    },
                    passHandler: function(e, row) {
                        DialogUi.textarea({
                            title: '认证',
                            text: '确定要通过该认证请求？',
                            callback: function(text, next) {
                                var curDialog = DialogUi.loading('操作中....');
                                server.teamCertificationApprove({
                                    _token: _token,
                                    team: row.data.id,
                                    memo: text || ''
                                }, function(resp) {
                                    curDialog.close();
                                    if (resp.code == 0) {
                                        next();
                                        row.destroy();
                                        DialogUi.message('已通过' + row.data.name + '认证');
                                    } else {
                                        DialogUi.message(resp.message || '请求出错了！');
                                    }
                                })
                            }
                        })
                    },
                    refuseHandler: function(e, row) {
                        DialogUi.textarea({
                            title: '认证',
                            text: '确定要拒绝该认证请求？',
                            callback: function(text, next) {
                                var curDialog = DialogUi.loading('操作中....');
                                server.teamCertificationReject({
                                    _token: _token,
                                    team: row.data.id,
                                    memo: text || ''
                                }, function(resp) {
                                    curDialog.close();
                                    if (resp.code == 0) {
                                        next();
                                        row.destroy();
                                        DialogUi.message('已拒绝' + row.data.name + '认证');
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
            TeamAuthenticationList.render();
        }
    }

    page.initialize();

})()