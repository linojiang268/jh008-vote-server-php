(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		server = K.server;

	var _token = $('input[name="_token"]').val();
	// refuse dialog
	var RefuseDialog = (function() {
		var refuseReasonList = ['身份不符合', '信息不真实', '成员已满'];

		function getReasonElement(text) {
			return '<div class="checkbox-wrap block">' +
						'<input type="checkbox" name="refuseReason" value="'+ text +'"><label><i class="icon iconfont"></i>'+ text +'</label>' +
					'</div>';
		}

		function getReasonEle() {
			var result = '';
			$.each(refuseReasonList, function(i, reason) {
				result += getReasonElement(reason);
			})
			return result;
		}

	    function _show(target, callback) {
			layer.open({
			    type: 0,
			    title: '拒绝',
			    area: ['400px', '500px'],
			    content: '<div class="refuse-dia">' +
					'<p class="title">拒绝理由：</p>' +
					'<div class="checkboxSels clearfix">' +
						getReasonEle() +
					'</div>'+
					'<p class="title">其他理由：</p>' +
					'<textarea name="" id="refuseContent" ></textarea>' + 
					'<div class="mt20"><a id="sureBtn" href="javascript:;" class="button button-orange">确定</a></div>' +
			    '</div>',
			    btn: false,
			    success: function(layero, index) {
			    	layero.find('.checkboxSels').lc_checkboxSel();
			    	layero.find('#sureBtn').click(function() {
			    		var refuseText = $.trim(layero.find('#refuseContent').val()),
			    			refuseValue = '';
			    		$.each(layero.find('input[type=checkbox]:checked'), function(index, refuseCheckbox) {
			    			refuseValue += refuseCheckbox.value + '; ';
			    		})
			    		//var mark = layero.find('#markContent').val().trim();
			    		callback(refuseValue + refuseText);
			    	})
			    }
			});
	    }

	    return {
	    	show: _show
	    }
	})()

	var ExamineList = (function() {

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
					function(data){
						return  '<a href="javascript:;" class="button button-lg-pre" id="through"><i class="icon iconfont"></i>通过</a>' +
								'<a href="javascript:;" class="button button-lg-pre ml10" id="refuse"><i class="icon iconfont"></i>拒绝</a>' + 
								'<a href="javascript:;" class="button button-lg-pre ml10" id="defriend"><i class="icon iconfont"></i>黑名单</a>';
					}
				],
				source: function(o, pTable) {
					var parms = {};
					parms.page = o.currentPage;
					server.listMemberPendingEnrollment(parms, function(resp){
						if (resp.code == 0) {
							pTable({totalPage: resp.pages, datas: resp.requests});
						} else {
							DialogUi.alert(resp.msg || '查询数据列表出错');
						}
					})
				},
				perNums: 20,
				events: {
					"click #through": "through",
					"click #refuse": "refuse",
					"click #defriend": "defriend",
					"click #edit": "editMemo"
				},
	 			eventsHandler: {
					through: function(e, row) {
						DialogUi.addMark({
							title: '添加备注',
							callback: function(memo, next) {
								var curDialog = DialogUi.loading('正在执行通过操作中...');
								server.approveMemberEnrollment({
									request: row.data.id,
									memo: memo,
									_token: _token
								}, function(resp) {
									curDialog.close();
									if (resp.code == 0) {
										next();
										row.destroy();	
										DialogUi.message('操作成功');							
									} else {
										DialogUi.message(resp.message || '操作失败');
									}
								})
							}
						})
					},
					refuse: function(e, row) {
						RefuseDialog.show(e.target, function(reason) {
							var curDialog = DialogUi.loading('正在执行通过操作中...');
							server.rejectMemberEnrollment({
								request: row.data.id,
								reason: reason,
								_token: _token
							}, function(resp) {
								if (resp.code == 0) {
									curDialog.close();
									row.destroy();
									DialogUi.message('操作成功');
								} else {
									DialogUi.message(resp.message || '操作失败');
								}
							})
						})
					},
					defriend: function(e, row) {
						DialogUi.addMark({
							title: '添加备注',
							callback: function(memo, next) {
								var curDialog = DialogUi.loading('正在执行通过操作中...');
								server.blacklistMemberEnrollment({
									request: row.data.id,
									memo: memo,
									_token: _token
								}, function(resp) {
									curDialog.close();
									if (resp.code == 0) {
										next();
										row.destroy();	
										DialogUi.message('操作成功');							
									} else {
										DialogUi.message(resp.message || '操作失败');
									}
								})							
							}
						})
					},
					editMemo: function(e, row) {
						DialogUi.addMark({
							title: '修改备注',
							content: row.data.memo || '',
							callback: function(memo, next) {
								if (memo != row.data.memo) {
									var curDialog = DialogUi.loading('正在修改备注中...');
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
			ExamineList.render();
		}
	}

	page.initialize();

})()