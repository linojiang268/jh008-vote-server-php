$(document).ready(function()
{
        var now = new Date(); 
        // var nowStr = now.format("yyyy-MM-dd HH:MM:ss");
        // $('.laydate-icon').val(nowStr);
        // format: 'YYYY/MM/DD hh:mm:ss',
        $('.date-w').on('click', 'input', function(event) {
        	event.preventDefault();
        	laydate({istime: true, format: 'YYYY-MM-DD hh:mm:ss'});
        });
        
        $("#activitySearch-btn").on('click', function(event) {
        	event.preventDefault();
        	event.stopPropagation();
        	activityListPage.tableRefresh();
        });
});   

(function(){    
//生成列表
	var util = K.util,
		DialogUi = K.dialogUi,
		server = K.server;

	var _token = $("input[name='_token']").val();

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

	var BlackList = (function() {
		//声明table变量
		var table= null;
		//table渲染函数，返回的是一个table对象
		function renderTablePag() {
			return util.PagTable({
				el: 'tableCon',
				columnNameList: [
					'title', 
					'begin_time',
                    'enrolled_num',
					function(data) {
						return data.status == -1 ? '已封停' : data.status == 0 ? '未发布' : data.status == 1 ? '已发布' : '';
					},
					function(data) {
						var result = '';
						if (data.status == 0) {
							result += '<a id="recovery" href="/community/activity/publish/'+ data.id +'" class="button button-m button-pre">编辑</a>';
						}
						if (data.status == 1) {
							result += '<a id="recovery" href="/community/activity/publish/'+ data.id +'" class="button button-m button-pre">修改</a>';
						}
						return result;
					},  
                    function(data) {
                        return '<a href="/community/activity/'+ data.id +'/manage/qrcode" class="button button-m button-pre">二维码</a>';
                    },
                    function(data) {
                        var result = '';
                        if (data.status == 1) {
                            result += '<a id="recovery" href="/community/activity/'+ data.id +'/manage/check" class="button button-m button-pre">管理</a>';
                        }
                        return result;
                    }
				],
                // perNums: 20,
				source:[],
				source: function(o, PagTable, option) {
					var parms = {};
					var date_start = $("#date-start").val(),
						date_end = $("#date-end").val();

					parms.page = o.currentPage;
					if ( date_start && date_end ) {
						parms.start = date_start;
						parms.end = date_end;
						parms._token = _token;
						server.searchTeamActivitiesByActivityTime(parms,function(resp) {
							if (resp.code == 0) {
								if ( !resp.total_num ) {
									DialogUi.alert("该时间段内没有任何活动,请重新选择查询日期。",function() {
										window.location.reload();
									});
									return;
								}

								PagTable({totalPage: Math.ceil(resp.total_num/15), datas: resp.activities || []});
							} else {
								alert(resp.message || '查询数据列表出错');
							}
						});
						return;
					}
					
					
					server.listdata(parms, function(resp){
						if (resp.code == 0) { 
							if (resp.activities.length) {
								PagTable({totalPage: Math.ceil(resp.total_num/15), datas: resp.activities});
							}
						} else {
							alert(resp.message || '查询数据列表出错');
						}
					})
				},
				events: {
					"click #delete": "deleteActivity",
					"click #detail": "detailHandler"
				},
				eventsHandler: {
					// recovery: function(e, row) {row.setData();row.refresh();
					// 	var curDialog = DialogUi.msg('正在恢复中...');
					// 	setTimeout(function(){
					// 		curDialog.close();
					// 	}, 1000);
					// },
					// inforFocus: function(e, row) {
					// 	var target = $(e.target);
					// 	target.addClass('form-control-long');
					// },
					deleteActivity: function(e, row) {
						var parms = {
							"activity": row.data.id,
							_token: _token
						};
						DialogUi.confirm({
                            text: '确定要删除此活动?',
                            okCallback: function() {
                                server.deleteActivityById(parms,function(resp) {
									if (resp.code == 0) {
	                                	row.trigger('destroy');
	                            	}else{
	                                	DialogUi.alert(row.data.name || '删除失败');
	                            	}
								});                            
                            }
                        });
						// var target = $(e.target);
					}/*,
					detailHandler: function(e, row) {
                        var waitDialog = DialogUi.loading('详情加载中...');
                        server.getActivity({
                            activity: row.data.id
                        }, function(resp) {
                            waitDialog.close();
                            if (resp.code == 0) {
                                detailDialog(resp.activity);
                            } else {
                                DialogUi.alert(resp.message || '详情加载失败');
                            }
                        });
					}*/
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

	var page = {
		initialize: function(){
			BlackList.render();
		},
		tableRefresh: function() {
			BlackList.refresh();
		}
	}

	page.initialize();

	window.activityListPage = page;

})()
