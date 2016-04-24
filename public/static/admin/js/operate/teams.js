$(function(){
    var util = K.util,
        DialogUi = K.dialogUi,
        server = K.server,
        common = K.common,
        size = K.LIST_SIZE;

    var _token = $('input[name="_token"]').val();

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

    var tagDialog = function (selectTags, tags, callback) {
        var setTags = new common.SetTags(selectTags, tags);
        DialogUi.open({
            area: ['400px', '400px'],
            content: '<div id="tagsCon"></div>',
            success: function(layero, index) {
                layero.find('#tagsCon').append(setTags.El);
            },
            yes: function() {
                var tags = setTags.getSelectTags();
                callback(tags);
            }
        })
    }

    var getTags = common.getTags,
        getFilters = common.getFilters;

    var TeamList = (function() {
        var table = null;
        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index', 
                    'name',
                    function(data){
                        return '<a href="javascript:;" id="detail" class="button button-orange button-m">查看详情</a>';
                    },
                    function(data) {
                        if (data.tags)
                            return '<span class="tags-len">' + data.tags.join('、') + '</span>';
                        return '';
                    },
                    function(data){
                        var result = '<a id="reTag" href="javascript:;" class="button button-orange button-m mr10">定义标签</a>';
                        if (data.status == 0) { // 正常
                            result += '<a id="seal" href="javascript:;" class="button button-orange button-m mr10">封停</a>';
                        } else if (data.status == 1) { // 封停
                            result += '<a id="freeze" href="javascript:;" class="button button-orange button-m">解封</a>';
                        }
                        return result;
                    }
                ],
                source: function(o, PagTable, option) {
                    var parms = {};
                    parms.page = o.currentPage;
                    parms.size = size;
                    $.extend(parms, getFilters() || {});
                    server.teamList(parms, function(resp){
                        if (resp.code == 0) {
                            PagTable({totalPage: resp.pages, datas: resp.teams});
                        } else {
                            DialogUi.alert(resp.message || '查询数据列表出错');
                        }
                    })
                },
                perNums: size,
                events: {
                    "click #detail" : "detailHandler",
                    "click #reTag"  : "reTagHandler",
                    "click #seal"   : "sealHandler",
                    "click #freeze" : "freezeHandler"
                },
                eventsHandler: {
                    detailHandler: function(e, row) {
                        detailDialog(row.data);
                    },
                    reTagHandler: function(e, row) {
                        var deferred = $.Deferred();
                        getTags(deferred);
                        deferred.done(function (tags) {
                            tagDialog(row.data.tags || [], tags, function(selectTags) {
                                var curDialog = DialogUi.loading('定义标签中...');
                                server.teamSetTag({
                                    tags: selectTags,
                                    team: row.data.id,
                                    _token: _token
                                }, function(resp) {
                                    curDialog.close();
                                    if (resp.code == 0) {
                                        row.setData({tags: requestTags});
                                        row.refresh();
                                        DialogUi.message('标签修改成功');
                                    } else {
                                        DialogUi.message(resp.message || '标签修改失败');
                                    }
                                });
                            });
                        });
                    },
                    sealHandler: function(e, row) {
                        DialogUi.confirm({
                            title: "封停操作",
                            text: "确定要封停" + row.data.name,
                            okCallback: function() {
                                var dialog = DialogUi.loading('正在操作中...');
                                server.teamForbidden({
                                    team: row.data.id,
                                    _token: _token
                                }, function(resp) {
                                    dialog.close();
                                    if (resp.code == 0) {
                                        table.refresh();
                                        DialogUi.message('操作成功');
                                    } else {
                                        DialogUi.alert(resp.message || '操作失败了！');
                                    }
                                })
                            }
                        });
                    },
                    freezeHandler: function(e, row) {
                        DialogUi.confirm({
                            title: "解封操作",
                            text: "确定要解封" + row.data.name,
                            okCallback: function() {
                                var dialog = DialogUi.loading('正在操作中...');
                                server.teamCancelForbidden({
                                    team: row.data.id,
                                    _token: _token
                                }, function(resp) {
                                    dialog.close();
                                    if (resp.code == 0) {
                                        table.refresh();
                                        DialogUi.message('操作成功');
                                    } else {
                                        DialogUi.alert(resp.message || '操作失败了！');
                                    }
                                })
                            }
                        });
                    }
                }
            })
        }

        function _render() {
            table = renderTablePag();
        }

        function _refresh() {
            table.refresh();
        }

        return {
            render: _render,
            refresh: _refresh
        }
    })()

    $('#serachBtn').click(function() {
        TeamList.refresh();
    })

    $('.filter-bar').on('click', 'a', function(e) {
        var target = $(e.target),
            parent = target.parents('.filter-bar');
        parent.find('dd').removeClass('filter-select');
        target.parent().addClass('filter-select');
        TeamList.refresh();
    })

    var page = {
        initialize: function(){
            TeamList.render();
        }
    }

    page.initialize();

})