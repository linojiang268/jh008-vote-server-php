$(function(){
    var util = K.util,
        DialogUi = K.dialogUi,
        server = K.server,
        common = K.common,
        size = K.LIST_SIZE;

    var _token = $('input[name="_token"]').val();
    // get tags.
    var getTags = common.getTags;

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

    function detailDialog(data) {
        var contentString = template('activity_detail_template', data);
        DialogUi.open({
            type: 1,
            title: data.title + '活动详情',
            area: ['700px', '600px'],
            shadeClose: true,
            content: contentString,
            success: function(layero, index) {
                var addressElement = layero.find('#detail')[0];
                addressElement.innerHTML = addressElement.innerText || addressElement.textContent;
            }
        })
    }

    // get filters.
    var getFilters = common.getFilters;


    var ActivitiesList = (function() {
        var table = null;
        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index', 
                    'title',
                    'begin_time',
                    'end_time',
                    function(data) {
                        var status = data.status;
                        return status == -1 ? '已封停' : status == 0 ? '未发布' : status == 1 ? '已发布' : '';
                    },
                    function(data) {
                        if (data.tags)
                            return '<span class="tags-len">' + JSON.parse(data.tags).join('、') + '</span>';
                        return '';
                    },
                    function(data){
                        return '<a href="javascript:;" id="detail" class="button button-orange button-m">查看详情</a>';
                    },
                    function(data){
                        var result = '<a id="reTag" href="javascript:;" class="button button-orange button-m mr10">定义标签</a>';
                        if (data.status == 1) {
                            result += '<a id="seal" href="javascript:;" class="button button-orange button-m mr10">封停</a>';
                        } else if (data.status == -1) {
                            result += '<a id="freeze" href="javascript:;" class="button button-orange button-m">解封</a>';
                        }
                        return result;       
                    }
                ],
                source: function(o, PagTable, option) {
                    var parms = {};
                    parms.page = o.currentPage;
                    parms.size = size;
                    parms._token = _token;
                    $.extend(parms, getFilters() || {});
                    server.activityList(parms, function(resp){
                        if (resp.code == 0) {
                            PagTable({totalPage: Math.ceil(resp.total_num/size), datas: resp.activities});
                        } else {
                            DialogUi.alert(resp.msg || '查询数据列表出错');
                        }
                    })
                },
                perNums: size,
                events: {
                    "click #detail"              : "detailHandler",
                    "click #reTag"               : "reTagHandler",
                    "click #seal"                : "sealHandler",
                    "click #freeze"              : "freezeHandler"
                },
                eventsHandler: {
                    detailHandler: function(e, row) {
                        var waitDialog = DialogUi.loading('详情加载中...');
                        server.activityDetail({
                            activity: row.data.id
                        }, function(resp) {
                            waitDialog.close();
                            if (resp.code == 0) {
                                detailDialog(resp.activity);
                            } else {
                                DialogUi.message(resp.message || '详情加载失败');
                            }
                        });
                    },
                    reTagHandler: function(e, row) {
                        var deferred = $.Deferred();
                        getTags(deferred);
                        deferred.done(function (tags) {
                            tagDialog((row.data.tags && JSON.parse(row.data.tags)) || [], tags, function(selectTags) {
                                var requestTags = JSON.stringify(selectTags);
                                var curDialog = DialogUi.loading('修改标签操作中...');
                                server.activitySettags({
                                    tags: requestTags,
                                    id: row.data.id,
                                    _token: _token
                                }, function(resp) {
                                    curDialog.close();
                                    if (resp.code == 0) {
                                        row.setData({tags: requestTags});
                                        row.refresh();
                                        DialogUi.message('标签修改成功');
                                    } else {
                                        DialogUi.message(resp.message || '更新失败');
                                    }
                                });
                            });
                        });
                    },
                    sealHandler: function(e, row) {
                        DialogUi.confirm({
                            title: "封停操作",
                            text: "确定要封停" + row.data.title,
                            okCallback: function() {
                                var dialog = DialogUi.loading('正在操作中...');
                                server.activityDelete({
                                    activity: row.data.id,
                                    _token: _token
                                }, function(resp) {
                                    dialog.close();
                                    if (resp.code == 0) {
                                        table.refresh();
                                        DialogUi.message('操作成功');
                                    } else {
                                        DialogUi.message(resp.message || '操作失败了！');
                                    }
                                })
                            }
                        });
                    },
                    freezeHandler: function(e, row) {
                        DialogUi.confirm({
                            title: "解封操作",
                            text: "确定要解封" + row.data.title,
                            okCallback: function() {
                                var dialog = DialogUi.loading('正在操作中...');
                                server.activityRestore({
                                    activity: row.data.id,
                                    _token: _token
                                }, function(resp) {
                                    dialog.close();
                                    if (resp.code == 0) {
                                        table.refresh();
                                        DialogUi.message('操作成功');
                                    } else {
                                        DialogUi.message(resp.message || '操作失败了！');
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
        ActivitiesList.refresh();
    })

    $('.filter-bar').on('click', 'a', function(e) {
        var target = $(e.target),
            parent = target.parents('.filter-bar');
        parent.find('dd').removeClass('filter-select');
        target.parent().addClass('filter-select');
        ActivitiesList.refresh();
    })

    var page = {
        initialize: function(){
            ActivitiesList.render();
        }
    }

    page.initialize();

})