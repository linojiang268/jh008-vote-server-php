(function(){

	var util = K.util,
		DialogUi = K.dialogUi,
		server = K.server;

	var _token = $('input[name="_token"]').val();

	// update  group  dialog
	var updateGroupDialog = (function() { 
	    function _show(groups, callback) {
    		return DialogUi.radio({
    			datas: groups,
    			callback: function(value, next) {
    				callback && callback(value, next);
    			}
    		});
	    }
	    return {
	    	show: _show
	    }
	})()

	var exportBtn = $('#export')
	function setExportLink(group, keyword) {
		var result = '';
		if (group) 
			result += 'group=' + group;
		if (keyword)
			result += 'keyword=' + keyword;
		exportBtn.attr('href', '/community/team/member/export?' + result);
	}

	exportBtn.click(function(e) {
		if (!exportBtn.attr('href')) {
			DialogUi.alert('查询列表为空');
		}
		e.stopPropogation();
	})

	// get groups
	var getGroups  = (function() {
		var groups = null;
		return function(callback) {
			if (!groups) {
				server.listTeamGroup({page: 1}, function(resp) {
					if (resp.code == 0) {
						groups = resp.groups;
						$.each(groups, function(i, group) {
							group.value = group.name;
						})
						callback(groups);
					} 
				})
			} else {
				callback(groups);
			}
		}
	})()

	// member list manager
	var memberList = (function(){
		var table;
		function renderTablePag() {
			return util.PagTable({
				el: 'tableCon',
				multiselect: true,
				columnNameList: [
					'index',
					'entrytime',
					'mobile',
					'name',
					/*function(data) {
						return data.group ? data.group.name : '未分组';
					},*/
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
						return '<span class="memo-text">'+ (data.memo || '') +'</span><a id="edit" class="edit-memo" href="javascript:;"><i class="icon iconfont"></i></a>';
					}/*,
					function(data){
						var result = '<a href="javascript:;" class="button" id="adjust">调整分组</a>';
						if (data.group) {
							result += '<a href="javascript:;" class="button ml10" id="cancel">取消分组</a>';
						}
						return result;	
					}*/
				],
				source: function(o, ptable, filter) {  //console.log(filter);
					var parms = {};
					parms.page = o.currentPage;
					if (filter.refresh.length) {
						$.each(filter.refresh, function(i, refresh) {
							if (refresh.key == 'search') {
								parms.keyword = refresh.value;
							}
							if (refresh.key == 'group') {
								parms.group = refresh.value;
							}
						})
					}
					server.listMembers(parms, function(resp){
						if (resp.code == 0) {
							setExportLink(parms.group || '', parms.key || '');
							ptable({totalPage: resp.pages, datas: resp.members});
						} else {
							DialogUi.alert(resp.msg || '查询数据列表出错');
						}
					})
				},
				perNums: 20,
				events: {
					"click #adjust": "adjustGroup",
					"click #cancel": "cancelGroup",
					"click #edit":   "editMemo"
				},
				eventsHandler: {
					adjustGroup: function(e, row) {
						getGroups(function(groups) {
							DialogUi.radio({
								datas: groups,
								curdata: row.data.group ? {
									value: row.data.group.name,
									name: row.data.group.name,
									id: row.data.group.id
								} : '',
								callback: function(data, next) {
									if (data.id == (row.data.group && row.data.group.id))
										return next();
									var parms = {
										_token: _token,
										member: row.data.id,
										to: data.id
									};
									if (row.data.group && row.data.group.id) {
										parms.from = row.data.group.id;
									}
									var curDialog = DialogUi.loading('正在调整分组中...');
									server.updateMemberGroup(parms, function(resp) {
										curDialog.close();
										if (resp.code == 0) {
											DialogUi.message('调整分组成功');
											next();
											row.setData({group: data});
											row.refresh();
										} else {
											DialogUi.message(resp.message || '调整分组失败');
										}
									})
								}
							})
						})
					},
					cancelGroup: function(e, row) {
						if (row.data.group && row.data.group.id) {
							var curDialog = DialogUi.loading('正在取消分组中...');
							server.updateMemberGroup({
								_token: _token,
								member: row.data.id
							}, function(resp) {
								curDialog.close();
								if (resp.code == 0) {
									row.setData({group: ''});
									row.refresh();
									DialogUi.message('取消分组成功');
								} else {
									DialogUi.message(resp.message || '取消分组失败');
								}
							})							
						}
					},
					editMemo: function(e, row) {
						DialogUi.addMark({
							title: '修改备注',
							content: row.data.memo || '',
							callback: function(memo, next) {
								if (memo != row.data.memo) {
									var curDialog = DialogUi.loading('正在修改中...');
									server.updateMember({
										memo: memo,
										member: row.data.id,
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
			// ajax datas
			table = renderTablePag();
		}

		function _refresh(datas) {
			table.refresh(datas);
		}

		function _clearStatus(name) {
			table.clearStatus(name);
		}

		function _getSelectMembers() {
			var members = table.getSelectAll(),
				result = [];
			$.each(members, function(index, member) {
				result.push(member.id);
			})
			return result;
		}

		return {
			render: _render,
			refresh: _refresh,
			clearStatus: _clearStatus,
			getSelectMembers: _getSelectMembers
		}
	})()

	// filter by group
	$('#groupFilter').on('click', 'a', function(e) {
		var target = $(e.target),
			id = target.attr('data-id'),
			text = target.text().trim();
		if (!id) {
			memberList.clearStatus('refresh');
			memberList.refresh();
		} else {
			$('#filterItemLinkTip').text(text);
			memberList.refresh({key: 'group', value: id});			
		}
	})

	// search manager by name | mobile
	$('#search').click(function() {
		var searchInputEl = $('#searchInput');
		var value = searchInputEl.val().trim();
		if (!value) {
			DialogUi.tip($(this), '搜索内容不能为空');
		} else {
			memberList.refresh({key:'search', value: value});
			searchInputEl.val('');
		}
	})

	// pitch cancel group
	$('#pitchCancelGroup').click(function(){
		var ids = memberList.getSelectMembers();
		server.updateMemberGroup({
			_token: _token,
			member: ids
		}, function(resp) {
			if (resp.code == 0) {
				DialogUi.message(resp.message || '取消分组成功');
				memberList.refresh();
			} else {
				DialogUi.alert(resp.message || '取消分组失败');
			}
		})
	})

	// pitch adjust group
	$('#pitchAdjustGroup').click(function(){
		var ids = memberList.getSelectMembers();
		if (ids.length <= 0) {
			return DialogUi.alert('你还没有选择成员');
		}
		getGroups(function(groups) {
			DialogUi.radio({
				datas: groups,
				callback: function(data, next) {
					server.updateMemberGroup({
						_token: _token,
						member: ids,
						to: data.id
					}, function(resp) {
						if (resp.code == 0) {
							DialogUi.message(resp.message || '调整分组成功');
							memberList.refresh();
						} else {
							DialogUi.alert(resp.message || '调整分组失败');
						}
					})
				}
			})
		})
	})

	var page = {
		initialize: function() {
			memberList.render();
		
		}
	}

	page.initialize();

})()