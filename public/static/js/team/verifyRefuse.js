(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		server = K.server;

	var RefuseList = (function() {
		var table;
		function renderTablePag() {
			return util.PagTable({
				el: 'tableCon',
				columnNameList: [
					'index', 
					function(data) {
						return data.initiator.mobile;
					},
					'name',					
					function(data){
						var result = '';
						if (data.requirements) {
							$.each(data.requirements, function(i, requirement) {
								result += requirement.value +'; ';
							})
						}
						if (result) {
							result = '<span class="infor-length">'+ result.slice(0, -2) +'</span>';
						}
						return result;
					},
					function(data) {
						return '<span title='+ data.memo +' class="memo-text">'+ (data.memo || '') +'</span><a id="edit" class="edit-memo" href="javascript:;"><i class="icon iconfont"></i></a>';
					},
					'reason'
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.page = o.currentPage;
					server.listMemberRejectedEnrollment(parms, function(resp){
						if (resp.code == 0) {
							PagTable({totalPage: resp.pages, datas: resp.requests});
						} else {
							DialogUi.alert(resp.msg || '查询数据列表出错');
						}
					})
				},
				events: {
					"click #edit": "editMemo"
				},
				perNums: 20,
				eventsHandler: {
					editMemo: function(e, row) {
						DialogUi.addMark({
							title: '修改备注',
							content: row.data.memo || '',
							callback: function(memo, next) {
								if (memo != row.data.memo) {
									var curDialog = DialogUi.loading('正在修改中');
									server.updateMemo({
										memo: memo,
										request: row.data.id,
										_token: $('input[name="_token"]').val()
									}, function(resp) {
										curDialog.close();
										if (resp.code == 0) {
											next();
											row.data.memo = memo;
											row.refresh();
											DialogUi.message('修改备注成功');
										} else {
											DialogUi.message(resp.message || '修改备注出错了!');
										}
									})
								} else {
									next();
								}
							}
						})
					}
				}
			});
		}

		function _render() {
			table = renderTablePag();
		}

		return {
			render: _render
		}
	})()

	var page = {
		initialize: function(){
			RefuseList.render();
		}
	}

	page.initialize();

})()