$(function() {
    var util = K.util,
        DialogUi = K.dialogUi,
        server = K.server;

    var ROLE_ADMIN = 'admin';
    var ROLE_OPERATOR = 'operator';
    var ROLE_ACCOUNTANT = 'accountant';

    var _token = $('input[name="_token"]').val();

    var AccountsList = (function() {

        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index',
                    'user_name',
                    function(data) {
                        var result = '';
                        switch(data.role) {
                            case ROLE_ADMIN : 
                                result = '系统管理员'; 
                                break;
                            case ROLE_OPERATOR : 
                                result = '运营管理员'; 
                                break;
                            case ROLE_ACCOUNTANT : 
                                result = '财务管理员'; 
                                break;                            
                        }
                        return result;
                    },
                    function(data) {
                        if (~[ROLE_ACCOUNTANT, ROLE_OPERATOR].indexOf(data.role)) {
                            return '<a href="javascript::" id="delete" class="button button-orange button-m">删除</a>' +
                                '<a href="javascript::" id="reset" class="ml10 button button-orange button-m">重置密码</a>'
                        }
                        return '';
                    }
                ],
                source: function(o, pTable) {
                    var parms = {};
                    parms.page = o.currentPage;
                    server.accountsList(parms, function(resp){
                        if (resp.code == 0) {
                            pTable({totalPage: resp.pages, datas: resp.users});
                        } else {
                            DialogUi.alert(resp.msg || '查询数据列表出错');
                        }
                    })
                },
                events: {
                    'click #delete': 'deleteAccount',
                    'click #reset': 'resetPassword'
                },
                eventsHandler: {
                    deleteAccount: function(e, row) {
                        DialogUi.confirm({
                            text: '你确认要删除此账户吗？',
                            okCallback: function() {
                                var curDialog = DialogUi.loading('删除操作中...');
                                server.removeAccount({
                                    user: row.data.id,
                                    _token: _token
                                }, function(resp) {
                                    curDialog.close();
                                    if (resp.code == 0) {
                                        row.destroy();
                                    } else {
                                        DialogUi.alert(resp.message || '删除操作出错了！');
                                    }
                                })
                            }
                        })
                    },
                    resetPassword: function(e, row) {
                        DialogUi.confirm({
                            text: '你确认要重置此账户密码？',
                            okCallback: function() {
                                var curDialog = DialogUi.loading('重置操作中...');
                                server.resetPassword({
                                    user: row.data.id,
                                    password: '123456',
                                    _token: _token
                                }, function(resp) {
                                    curDialog.close();
                                    if (resp.code == 0) {
                                        DialogUi.message('重置成功');
                                    } else {
                                        DialogUi.alert(resp.message || '重置操作出错了！');
                                    }
                                })
                            }
                        })
                    }
                }
            });
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
            AccountsList.render();
        }
    }

    page.initialize();

})