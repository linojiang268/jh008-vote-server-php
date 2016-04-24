(function($){
	var util = K.util;
	var transfer_list = (function(){
		function renderTablePag(){
			return util.PagTable({
				ThList: ['序号', '日期', '提现金额', '提现方式', '姓名', '支付宝账号','查看凭证','状态'],
				columnNameList: [
					'index', 
					'time',
					'withdrawalAmount',
					'withdrawalType',
					'name',
					'paymentAccount',
					function(data){
						return '<a href="javascript:;" id="viewInfo" class="button btn-view">查看凭证</a>'
					},
					'status'
				],
				source: function(o, pTable) {
					var parms = {};
					//parms.page = o.currentPage;
					//parms.size = static.actListPerNum;
					//server.acts(parms, function(resp){
						var resp = {
							code: 0,
							body: {
								members: [
									{id:1,time: '2012/12/12 12:00:00',withdrawalAmount: '400',withdrawalType:'支付宝',name:'张三',paymentAccount: '13211112222',status: '成功'},
									{id:2,time: '2012/12/12 12:00:00',withdrawalAmount: '400',withdrawalType:'支付宝',name:'张三',paymentAccount: '13211112222',status: '成功'},
									{id:3,time: '2012/12/12 12:00:00',withdrawalAmount: '500',withdrawalType:'支付宝',name:'张三',paymentAccount: '13211112222',status: '成功'},
									{id:4,time: '2012/12/12 12:00:00',withdrawalAmount: '600',withdrawalType:'支付宝',name:'张三',paymentAccount: '13211112222',status: '成功'},
									{id:5,time: '2012/12/12 12:00:00',withdrawalAmount: '800',withdrawalType:'支付宝',name:'张三',paymentAccount: '13211112222',status: '成功'}
								],
								total_num: 25
							}
						};
						if (resp.code == 0) {
							if (resp.body.members.length) {
								pTable({totalPage: Math.ceil(resp.body.total_num/5), datas: resp.body.members});
							}
						} else {
							alert(resp.msg || '查询数据列表出错');
						}
					//})
				},
				perPageNums: 5
			},
			{
				"click #viewInfo": "viewInfo",
			},
			{
				viewInfo: function(){
					alert("查看凭证");
				}
			});
		}

		function _render(){
			table = renderTablePag();
			$(".transfer_list-w").html(table.El);
		}
		return {
			render : _render
		}
	})();

	var page = {
		initialize : transfer_list.render
	};

	page.initialize();
})(jQuery)