(function($){
	var util = K.util,
		Server = K.server,
		Dialog = K.dialogUi;

	var payment = (function() {

		function renderTablePag(aid) {
			return util.PagTable({
				ThList: ['序号', '姓名','电话','支付渠道','订单号','金额（元）', '付款日期'],
				columnNameList: [
					'id', 
					function(data){
						return data.user.nick_name
					},
					function(data){
						return data.user.mobile
					},
					function(data){
						return data.channel == 1 ? '支付宝':'微信';
					},
					'order_no',
					function(data){
						var yuan = Math.floor(data.fee/100),
							jiao = Math.floor((data.fee%100)/10),
							fen  = Math.floor((data.fee%100)%10);
						return yuan+"."+jiao+fen;
					},
					'payed_at'
				],
				source: function(o, pTable) {
					var parms = {};
					parms.page = o.currentPage;
					// parms.size = static.actListPerNum;
					//server.acts(parms, function(resp){
					parms.activity = aid;
					parms.size = 10;
					Server.financeIndexDetail(parms,function(resp) {
						console.log(resp);
						if (resp.code == 0) {
							pTable({totalPage: Math.ceil(resp.total/10), datas: resp.payments});
						} else {
							Dialog.alert(resp.message || '查询数据列表出错');
						}
					});
				}
			});
		}

		function _render(aid) {
			var table = renderTablePag(aid);
			$('.payment-table').html(table.El);
		}

		return {
			render: _render
		}
	})()

	var page = {
		initialize: function(activity_id){
			payment.render(activity_id);
		}
	}
	
	window.page = page;
})(jQuery)


$(function(){
	page.initialize(activity_id);	
})
