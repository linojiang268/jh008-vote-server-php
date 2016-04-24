(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		Server = K.server,
		dropdownPage = util.dropdownPage;
	var _token = $("input[name='_token']").val(); 
	var dropPage = (function(){

		function renderDropDownPage(){
			var dropdownUl = $(".ui-select1 .dropdown-menu");
			var pageNation = $(".ui-select1 .dropdown-page-w .page-index-w"); 
			// dropdownPage.init(data,dropdownUl,pageNation);
			var parms = {};
				parms.page = 1;
				// parms.team = 1;
				Server.listdata(parms, function(resp){
					if (resp.code == 0) {

						// <li><a data-aid="+el.id+" data-tid="+el.team_id+" href='javascript:;'>"+el.title+"</a></li>
						//追加只与社团有关和只与活动有关的选项
						resp.activities.unshift(
							{'id':'','title':'<span style="color:red">● 所有类型资讯</span>'},
							{'id':'-1','title':'<span style="color:red">● 只与社团有关的资讯</span>'},
							{'id':'0','title':'<span style="color:red">● 只与活动有关的资讯</span>'}
						); 
						dropdownPage.init(resp.activities,dropdownUl,pageNation);
						pageNation.parent().css("top",dropdownUl.height()+34);
						//默认点击第一个列表
						$('.dropdown-menu li:first a').trigger('click');

					} else {
						alert(resp.message || '查询数据列表出错');
					}
				});
			//下拉列表点击事件绑定
			$(".ui-select1").on('click','.dropdown-menu a',function(e){
				e.stopPropagation();
				var activity_id = $(this).data('aid');
				BlackList.render(activity_id);
			});
		}

		return {
			render: renderDropDownPage
		}
	})();


	var BlackList = (function() {

		function renderTablePag(activity_id) {

			destroyTable();

			return util.PagTable({
				el: 'tableCon',
				columnNameList: [
					'index', 
					'publishTime',
					'title',
                    /*
                    'infro',
					function(data){
						return '<div class="infor-wrap"><input type="text" id="infor" class="form-control" value="'+ data.infro +'" /></div>';
					},*/
					function(data){
						return  '<a href="/wap/news/detail?news_id='+data.id+'" target="_blank" class="button button-pre"><i class="icon iconfont"></i>查看</a>&nbsp;&nbsp;'+
                        '<a id="recovery" href="/community/news/'+data.id+'/update" class="button button-pre"><i class="icon iconfont"></i>编辑</a>&nbsp;&nbsp;'+
                        '<a href="javascript:;" id="deleteNews" class="button button-pre"><i class="icon iconfont"></i>删除</a>';
					}
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.page = o.currentPage;
					// parms.size = 5;
					// parms.team_id = Number(sessionStorage.getItem('tid'));
					if (activity_id || activity_id == 0) {
						parms.activity_id = activity_id;
					}
					//server.acts(parms, function(resp){
					Server.getTeamNews(parms,function(resp) {
						if (resp.code == 0) {
							// if (resp.news.length) {
								PagTable({totalPage: resp.pages, datas: resp.news});
							// }
						} else {
							alert(resp.msg || '查询数据列表出错');
						}
					})
						/*var resp = {
							code: 0,
							body: {
								members: [{id:1,	name: '习近平电贺韦约尼斯当选拉脱维亚总统',time: '2012/12/12 12:00:00',newsId:"123"},
								{id:2,	name: '习近平电贺韦约尼斯当选拉脱维亚总统',time: '2012/12/12 12:00:00',newsId:"123"},
								{id:3,	name: '习近平电贺韦约尼斯当选拉脱维亚总统',time: '2012/12/12 12:00:00',newsId:"123"},
								{id:4,	name: '习近平电贺韦约尼斯当选拉脱维亚总统',time: '2012/12/12 12:00:00',newsId:"123"},
								{id:5,	name: '习近平电贺韦约尼斯当选拉脱维亚总统',time: '2012/12/12 12:00:00',newsId:"123"}],
								total_num: 20
							}
						};
						if (resp.code == 0) {
							if (resp.body.members.length) {
								PagTable({totalPage: Math.ceil(resp.body.total_num/5), datas: resp.body.members});
							}
						} else {
							alert(resp.msg || '查询数据列表出错');
						}*/
					//})
				},
				events: {
					"click #deleteNews": "delete"
				},
				eventsHandler: {
					delete: function(e, row) {
						var parms = {
							"_token": _token
						}
						DialogUi.loading("删除活动中");
						Server.deleteNews(parms,row.data.id,function(resp){
							if (resp.code == 0) {
								DialogUi.alert(resp.message,function() {
									row.trigger('destroy');
								});
							}else {
								DialogUi.alert(resp.message || "删除失败");
							}
						});
						// row.trigger('destroy');
					}
				}
			})
		}

		function destroyTable(){
			$("#tableCon tbody").children().remove();
			$("#tableCon .paginate-con").remove();
		}

		function _render(activity_id) {
			var table = renderTablePag(activity_id);
		}

		return {
			render: _render
		}
	})()

	var page = {
		initialize: function(){
			// BlackList.render();
			dropPage.render();
		}
	}

	page.initialize();

})()


