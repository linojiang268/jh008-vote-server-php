(function() {
    var server = K.server,
        util = K.util,
        DialogUi = K.dialogUi;

    var _token = $('input[name="_token"]').val();
    var GroupList = (function(){
        var table;

        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                paginate: false,
                columnNameList: [
                    'index',
                    'name',
                    function(data){
                        return  '<a href="javascript:;" class="button button-m button-orange" id="delete">删除</a>' +
                                '<a href="javascript:;" class="button button-m button-orange ml10" id="update">修改</a>';
                    }
                ],
                source: function(o, ptable, filter) { 
                    var parms = {};
                    //
                    server.listTeamGroup(parms, function(resp){
                        if (resp.code == 0) {
                            if (resp.groups.length) {
                                ptable({totalPage: Math.ceil(resp.groups.length/20), datas: resp.groups});
                            }
                        } else {
                            DialogUi.alert(resp.msg || '查询数据列表出错');
                        }
                    })
                },
                perNums: 50,
                events: {
                    "click #delete": "deleteGroup",
                    "click #update": "updateGroup"
                },
                eventsHandler: {
                    deleteGroup: function(e, row) {
                        DialogUi.confirm({
                            text: '确定要删除组' + row.data.name + '?',
                            okCallback: function() {
                                var dialog = DialogUi.loading('正在删除分组中...');
                                server.deleteTeamGroup({
                                    group: row.data.id,
                                    _token: _token
                                }, function(resp) {
                                    dialog.close();
                                    if (resp.code == 0) {
                                        row.trigger('destroy');
                                        DialogUi.message(row.data.name + '删除成功');
                                    } else {
                                        DialogUi.message(row.data.name || '删除失败');
                                    }
                                })                                
                            }
                        })
                    },
                    updateGroup: function(e, row) {
                        CreateGroupDialog.show({
                            title: '修改组',
                            btnText: '修改',
                            value: row.data.name,
                            callback: function(groupName, next) {
                                if (groupName != row.data.name) {
                                    var dialog = DialogUi.loading('正在修改中...');
                                    server.updateTeamGroup({
                                        name: groupName, 
                                        group: row.data.id,
                                        _token: _token
                                    }, function(resp) {
                                        dialog.close();
                                        if (resp.code == 0) {
                                            row.setData({name: groupName});
                                            row.refresh();
                                            next();
                                            DialogUi.message(row.data.name + '修改成功');
                                        } else {
                                            DialogUi.message(row.data.name || '修改失败');
                                        }
                                    })
                                }
                            }
                        })
                    }
                }
            });
        }

        function _render() {
            // ajax datas
            table = renderTablePag();
        }

        return {
            render: _render
        }
    })()


    // create group dialog
    var CreateGroupDialog = (function() {
        /**
         * @parms {Object} options
         *      - title {String} dialog title
                - btnText {String} btn text
                - value {String} default group name
         */
        function _show(options) {
            layer.open({
                type: 0,
                title: options.title || '创建组',
                area: ['420px', '200px'],
                content: '<div class="addwl-dia">' +
                            '<form id="addwlForm" class="ui-form">' +
                                '<div class="ui-form-item">' +
                                    '<label for="" class="ui-label">组名称：</label>' +
                                    '<div class="ui-form-item-wrap"><input id="groupName" value="'+ (options.value || "") +'" name="name" class="form-control w200" type="text" ></div>' +
                                '</div>' + 
                                '<div class="ui-form-item">' +
                                    '<label for="" class="ui-label ui-label-industry">&nbsp;</label>' +
                                    '<div class="ui-submit-wrap">' +
                                        '<input type="submit" class="ui-form-submit" id="createBtn" value="'+ (options.btnText || "创建") +'">' +
                                    '</div>' +
                                '</div>' + 
                            '</form>' +
                        '</div>',
                btn: false,
                success: function(layero, index) {
                    layero.find('#addwlForm').validate({
                        rules: {
                            name: {
                                required: true,
                                maxlength: 16
                            }
                        },
                        messages: {
                            name: { 
                                required: '组名称不能为空',
                                maxlength: '组名称不能超过16位'
                            }
                        },
                        keyup: false,
                        submitHandler: function(form) {
                            var name = $.trim(layero.find('#groupName').val());
                            options.callback &&  options.callback(name, function() {
                                layer.close(index);
                            })
                        }
                    });
                }
            });
        }
        return {
            show: _show
        }
    })()

    // create group handler
    $('#createGroup').click(function() {
        CreateGroupDialog.show({
            callback: function(groupName, next) {
                var dialog = DialogUi.loading('正在创建中...');
                server.createTeamGroup({
                    name: groupName,
                    _token: _token
                }, function(resp) {
                    dialog.close();
                    if (resp.code == 0) {
                        DialogUi.message(groupName + '已经创建成功');
                        location.reload();
                    } else {
                        DialogUi.message(resp.message || '创建组失败');
                    }
                })
            }
        })
    })

    var page = {
        initialize: function() {
            GroupList.render();
        }
    }

    page.initialize();
})()