$(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		server = K.server;
	var _token = $("input[name='_token']").val(); 

	function tip(text, callback) {
		return DialogUi.alert(text, callback);
	}

	var degrees = [
		"大专以下", "大专", "本科", "本科以上"
	];


	var auditList = (function() {
		var table;
		function renderTablePag() {
			return util.PagTable({
				el: 'aduit-table',
				columnNameList: [
					'index', 
					'name',
					function(data){
						return data.gender == 1 ? "男" : "女";
					},
					'id_number',
					'date_of_birth',
					'mobile',
					'height',
					function(data) {
						return (data.graduate_university ? data.graduate_university : '--' ) + '/' + degrees[data.degree];
					},
					'yearly_salary',
					'work_unit',
                    function() {
                    	return '<a href="javascript:;" id="detail" class="button button-m button-blue">查看详情</a>';
                    },
					function(data){
						return '<a href="javascript:;" id="pass" class="button button-m button-blue">通过</a>' +
							'<a href="javascript:;" id="refuse" class="button button-m button-blue ml10">拒绝</a>';
					}
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.page = o.currentPage;
					server.pendingList(parms, function(resp){
						if (resp.code == 0) {
							PagTable({totalPage: resp.pages, datas: resp.attendants || []});
						} else {
							tip(resp.msg || '查询数据列表出错');
						}
					});
				},
				events: {
					"click #pass":   "passHandler",
					"click #refuse": "refuseHandler",
					"click #detail": "detailHandler"
				},
				eventsHandler: {
					passHandler: function(e, row) {
						var attendant = row.data.id;
						server.approve({attendant: attendant, _token: _token}, function(resp){
							if (resp.code == 0) {
								location.reload();
							} else {
								tip(resp.message || '通过操作出错了');
							}
						})
					},
					refuseHandler: function(e, row) {
						var attendant = row.data.id;
						server.remove({attendant: attendant, _token: _token}, function(resp){
							if (resp.code == 0) {
								location.reload();
							} else {
								tip(resp.message || '拒绝操作出错了');
							}
						})
					},
					detailHandler: function(e, row) {
						var attendant = row.data;
						attendant.universityAndDegree = (attendant.graduate_university ? attendant.graduate_university: '--') +
														'/' +  degrees[attendant.degree];

						var content = template.render('detail_template', attendant);

						DialogUi.open({
							title: '成员详情',
							content: content,
							area: ['800px', '700px']
						});
					}
				}
			})
		}

		function _render() {
			table = renderTablePag();
		}
		return {
			render: _render
		}
	})();

	var page = {
		initialize: function(){
			auditList.render();
		}
	}

	page.initialize();




	var passedList = (function() {
		var table;
		function renderTablePag() {
			return util.PagTable({
				el: 'passed-table',
				columnNameList: [
					'index',
					'name',
					function(data){
						return data.gender == 1 ? "男" : "女";
					},
					'id_number',
					'date_of_birth',
					'mobile',
					'height',
					function(data) {
						return (data.graduate_university ? data.graduate_university : '--' ) + '/' + degrees[data.degree];
					},
					'yearly_salary',
					'work_unit',
					function() {
						return '<a href="javascript:;" id="detail" class="button button-m button-blue">查看详情</a>';
					}
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.page = o.currentPage;
					server.approvedList(parms, function(resp) {
						if (resp.code == 0) {
							PagTable({totalPage: resp.pages, datas: resp.attendants || []});
						} else {
							tip(resp.msg || '查询数据列表出错');
						}
					});
				},
				events: {
					"click #detail": "detailHandler"
				},
				eventsHandler: {
					detailHandler: function(e, row) {
						var attendant = row.data;
						attendant.universityAndDegree = (attendant.graduate_university ? attendant.graduate_university: '--') +
							                            '/' +  degrees[attendant.degree];

						var content = template.render('detail_template', attendant);

						DialogUi.open({
							title: '成员详情',
							content: content,
							area: ['800px', '700px']
						});
					}
				}
			})
		}

		function _render() {
			table = renderTablePag();
		}
		return {
			render: _render
		}

	})();

	var page = {
		initialize: function(){
			passedList.render();
		}
	}

	page.initialize();
});