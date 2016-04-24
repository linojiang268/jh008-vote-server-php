(function($){
	$(function(){
		var start = {
		    elem: '#date-start',
		    format: 'YYYY/MM/DD',
		    min: laydate.now(), //设定最小日期为当前日期
		    max: '2099-06-16 23:59:59', //最大日期
		    istime: true,
		    istoday: false,
		    choose: function(datas){
		         end.min = datas; //开始日选好后，重置结束日的最小日期
		         end.start = datas //将结束日的初始值设定为开始日
	    	}
		};
		var end = {
		    elem: '#date-end',
		    format: 'YYYY/MM/DD',
		    min: laydate.now(),
		    max: '2099-06-16 23:59:59',
		    istime: true,
		    istoday: false,
		    choose: function(datas){
		        start.max = datas; //结束日选好后，重置开始日的最大日期
		    }
		};
		laydate(start);
		laydate(end);
	});
	var util = K.util;
	var inventory = (function() {

		function renderTablePag() {
			return util.PagTable({
				ThList: ['序号', '日期', '类型', '收支(元)'],
				columnNameList: [
					'index', 
					'time',
					'type',
					function(data){
						var tag = data.bPay.slice(0, 1);
						if (tag === "+") {
							return '<span class="positive">'+data.bPay+'</span>';
						}else if (tag === "-") {
							return '<span class="negative">'+data.bPay+'</span>';
						};
						
					},
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
									{id:1,time: '2012/12/12 12:00:00',type: '跑步',bPay:'+129.00'},
									{id:2,time: '2012/12/12 12:00:00',type: '游泳',bPay:'+129.00'},
									{id:3,time: '2012/12/12 12:00:00',type: '健身',bPay:'-129.00'},
									{id:4,time: '2012/12/12 12:00:00',type: '吃火锅',bPay:'+129.00'},
									{id:5,time: '2012/12/12 12:00:00',type: '遛弯儿',bPay:'-129.00'}
								],
								total_num: 20
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
			});
		}

		function _render() {
			var table = renderTablePag();
			$('.inventory-table').html(table.El);
		}

		return {
			render: _render
		}
	})()

	var page = {
		initialize: function(){
			inventory.render();
		}
	}

	page.initialize();
	
})(jQuery)
