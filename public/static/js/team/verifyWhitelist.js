(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		server = K.server;

	var _token = $('input[name="_token"').val();

	var WhiteList = (function() {
		var table;
		function renderTablePag() {
			return util.PagTable({
				el: 'tableCon',
				columnNameList: [
					'index', 
					'mobile',
					'name',
					function(data) {
						return '<span class="memo-text">'+ data.memo +'</span><a id="edit" class="edit-memo" href="javascript:;"><i class="icon iconfont"></i></a>';
					},
					function(data) {
						return '<a href="javascript:;" id="delete" class="button button-m button-orange">删除</a>';
					}
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.page = o.currentPage;
					server.whitelistList(parms, function(resp){
						if (resp.code == 0) {
							PagTable({totalPage: resp.pages, datas: resp.whitelist});
						} else {
							DialogUi.alert(resp.msg || '查询数据列表出错');
						}
					})
				},
				perNums: 20,
				events: {
					"click #edit":   "editMemo",
					"click #delete": "deleteHandler"
				},
				eventsHandler: {
					editMemo: function(e, row) {
						DialogUi.addMark({
							title: '修改备注',
							content: row.data.memo || '',
							callback: function(memo, next) {
								if (memo != row.data.memo) {
									var curDialog = DialogUi.loading('正在修改中...');
									server.updateWhitelistMemo({
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
											DialogUi.message(resp.message || '修改备注出错');
										}
									})
								} else {
									next();
								}
							}
						})
					},
					deleteHandler: function(e, row) {
						var curDialog = DialogUi.loading('正在删除中...');
						server.replywhitelistMemberEnrollment({
							mobile: row.data.mobile,
							_token: $('input[name="_token"]').val()
						}, function(resp) {
							curDialog.close();
							if (resp.code == 0) {
								DialogUi.message('删除成功');
								row.destroy();
							} else {
								DialogUi.message(resp.message || '删除出错');
							}
						});
					}
				}
			});
		}

		function _render() {
			table = renderTablePag();
		}

		function _refresh(options) {
			table.refresh(options);
		}

		return {
			render: _render,
			refresh: _refresh
		}
	})()

	// 手动添加白名单弹出框
	var addWhitelistDialog = (function() {
	    function _show(callback) {
			layer.open({
			    type: 0,
			    title: '添加白名单',
			    area: ['420px', '260px'],
			    content: '<div class="addwl-dia">' +
			    			'<form id="addwlForm" action="/ssss" class="ui-form">' +
			    				'<div class="ui-form-item">' +
			    					'<label for="" class="ui-label">手机号：</label>' +
			    					'<div class="ui-form-item-wrap"><input name="mobile" id="mobile" class="form-control w200" type="text" ></div>' +
			    				'</div>' + 
			    				'<div class="ui-form-item">' +
			    					'<label for="" class="ui-label">群名称：</label>' +
			    					'<div class="ui-form-item-wrap"><input name="name" id="name" class="form-control w200" type="text" ></div>' +
			    				'</div>' + 
			    				'<div class="ui-form-item">' +
			    					'<label for="" class="ui-label">备注：</label>' +
			    					'<div class="ui-form-item-wrap"><input name="name" id="memo" class="form-control w200" type="text" ></div>' +
			    				'</div>' + 
			    				'<div class="ui-form-item">' +
			    					'<label for="" class="ui-label ui-label-industry">&nbsp;</label>' +
			    					'<div class="ui-submit-wrap">' +
			    						'<input type="submit" class="ui-form-submit" id="saveProfile" value="创建">' +
			    					'</div>' +
			    				'</div>' + 
			    			'</form>' +
			    		'</div>',
			    btn: false,
			    success: function(layero, index) {
					layero.find('#addwlForm').validate({
						rules: {
							mobile: "isPhone",
							name: "required"
						},
						debug: true,
						messages: {
							isPhone: "请输入正确的电话号码",
							name: '群名称不能为空'
						},
						keyup: false,
						submitHandler: function(form) {
							var mobile = layero.find('#mobile').val().trim(),
								name = layero.find('#name').val().trim(),
								memo = layero.find('#memo').val().trim();
							callback(mobile, name, memo, function() {
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



	// 初始化Web Uploader
	$('#add').click(function() {
		addWhitelistDialog.show(function(mobile, name, memo, next) {
			var curDialog = DialogUi.loading('正在添加中....');
			server.whitelistMemberEnrollment({
				mobile: mobile,
				name: name,
				memo: memo,
				_token: _token
			}, function(resp) {
				curDialog.close();
				if (resp.code == 0) {
					next();
					WhiteList.refresh();
					DialogUi.message('添加成功');
				} else {
					DialogUi.message(resp.message || '添加失败');
				}
			})
		});
	})

	function setUploader() {
        var uploader = WebUploader.create({
            formData: {_token: _token},
            fileVal: 'whitelist',
            auto: true,
            swf: '/static/plugins/webuploader/Uploader.swf',
            server: '/community/team/member/enrollment/whitelist/import',
            pick: {
            	id: '#filePicker',
            	multiple: true
            },
            method: 'post'/*,
            accept: {
            	extensions: 'xls, xlsx'
            }*/
        });

        uploader.on('uploadSuccess', function(file, resp) {
            if (resp.code === 0) {
                //WhiteList.refresh();
                location.reload();
            } else {
            	//WhiteList.refresh();
            }
        });

        uploader.on('complete', function(file) {
        	location.reload();
        })
	}

	var page = {
		initialize: function(){
			if ($('#tableCon').length) {
				WhiteList.render();
				setUploader();
			}
		}
	}

	page.initialize();

})()


