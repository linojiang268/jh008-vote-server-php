(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		server = K.server;

	var BlackList = (function() {

		function renderTablePag() {
			return util.PagTable({
				el: 'tableCon',
				columnNameList: [
					'index', 
					'mobile',
					'name',
					function(data){
						var result = '';
						if (data.requirements) {
							$.each(data.requirements, function(i, requirement) {
								result += requirement.value +';&nbsp;&nbsp;&nbsp;&nbsp;';
							})
						}
						if (result) {
							result = '<span class="infor-length">'+ result +'</span>';
						}
						return result;
					},
					function(data) {
						return '<span title='+ data.memo +' class="memo-text">'+ data.memo +'</span><a id="edit" class="edit-memo" href="javascript:;"><i class="icon iconfont"></i></a>';
					},
					function(data){
						return  '<a id="delete" href="javascript:;" class="button button-lg-pre">删除</a>';
					}
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.page = o.currentPage;
					server.blacklistList(parms, function(resp){
						if (resp.code == 0) {
								PagTable({totalPage: resp.pages, datas: resp.blacklist});
						} else {
							alert(resp.msg || '查询数据列表出错');
						}
					})
				},
				perNums: 20,
				events: {
					"click #delete": "deleteHandler",
					"click #edit": "editMemo"
				},
				eventsHandler: {
					deleteHandler: function(e, row) {
						var curDialog = DialogUi.loading('正在恢复中...');
						server.replyblacklistMemberEnrollment({
							mobile: row.data.mobile,
							_token: $('input[name="_token"]').val()
						}, function(resp) {
							curDialog.close();
							if (resp.code == 0) {
								row.destroy();
								DialogUi.message('操作成功');
							} else {
								DialogUi.message(resp.message || '删除出错了！');
							}
						})
					},
					editMemo: function(e, row) {
						DialogUi.addMark({
							title: '修改备注',
							content: row.data.memo || '',
							callback: function(memo, next) {
								if (memo != row.data.memo) {
									var curDialog = DialogUi.loading('正在修改中');
									server.updateBlacklistMemo({
										memo: memo,
										mobile: row.data.mobile,
										_token: $('input[name="_token"]').val()
									}, function(resp) {
										curDialog.close();
										if (resp.code == 0) {
											next();
											row.data.memo = memo;
											row.refresh();
											DialogUi.message('修改备注成功');
										} else {
											DialogUi.message(resp.message || '修改备注出错了');
										}
									})
								} else {
									next();
								}
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
			BlackList.render();
		}
	}

	page.initialize();

})()