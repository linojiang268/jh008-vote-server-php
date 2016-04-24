(function($){
	var util = K.util,
		Server = K.server,
		Dialog = K.dialogUi;

	var payment = (function() {

		function renderTablePag() {
			return util.PagTable({
				ThList: ['序号', '日期', '活动名称', '总金额（元）'],
				columnNameList: [
					'index', 
					'time',
					function(data){
						// return "<a class='activity-title' href='index/"+data.activity.activity_id+"'>"+data.activity.title+"</a>"
						return data.activity.title
					},
					
					function(data){
						var yuan = Math.floor(data.total_fee/100),
							jiao = Math.floor((data.total_fee%100)/10),
							fen  = Math.floor((data.total_fee%100)%10);
						return "<a class='activity-title' href='index/"+data.activity.activity_id+"'>"+yuan+"."+jiao+fen+"</a>";
					}
				],
				source: function(o, pTable) {
					var parms = {};
					parms.page = o.currentPage;
					// parms.size = static.actListPerNum;
					//server.acts(parms, function(resp){
					// parms.team = 1;
					parms.size = 10;
					Server.financeIndex(parms,function(resp) {
						if (resp.code == 0) {
							if (resp.incomes.length) {
								$.each(resp.incomes,function(index, el) {
									this["time"] = el.activity["begin_time"];
									this["activity"] = {
										"title": el.activity["title"],
										"activity_id": el.activity["id"]
									}
									this["chargeType"] = "报名费";
								});
								pTable({totalPage: Math.ceil(resp.total/10), datas: resp.incomes});
							}else {
								Dialog.alert(resp.message || '没有数据。')
							}
						} else {
							Dialog.alert(resp.message || '查询数据列表出错');
						}
					});
				}
			});
		}

		function _render() {
			var table = renderTablePag();
			$('.payment-table').html(table.El);
		}

		return {
			render: _render
		}
	})()

	var page = {
		initialize: function(){
			payment.render();
		}
	}
	page.initialize();

})(jQuery)