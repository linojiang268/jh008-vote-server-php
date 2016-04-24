Function.prototype.method = function(name,fn) {
	this.prototype[name] = fn;
	return this;//返回this供链式声明方法
};

(function($) {
	var List = function() {
		this.ul = null;
		this.pageWrap = null;
		this.listData = null;
		this.currentPage = null;
		this.defaultPageIndex = 1;
		this.defaultPageNum = 5;
	}

	List.method('init',function(data,ulSelector,pageSelector) {
		this.ul = ulSelector;
		this.pageWrap = pageSelector;
		this.listData = data || [];
		//渲染分页对象，并设置默认页为1，从而渲染列表
		this.renderPage().setPage( this.defaultPageIndex );
	});

	List.method('renderList',function(data){
		if (!data) {
			throw "没有活动数据";
			return false;
		}
		var listArr = [],
			tid;
		$.each(data,function(index, el) {
				tid = el.team_id;
			if ( !el.id ) {
				var liStr = "<li><a data-tid="+sessionStorage.getItem('tid', tid)+" href='javascript:;'>"+el.title+"</a></li>";
			}else {
				var liStr = "<li><a data-aid="+el.id+" data-tid="+el.team_id+" href='javascript:;'>"+el.title+"</a></li>";
			}
			listArr.push(liStr);
		});
		//将team_id存到session里去,后面有用
		sessionStorage.setItem('tid', tid);

		this.ul.children().remove();
		this.ul.append(listArr.join(""));
		
		//就算ul的高度，动态改变分页组件的top以达到自适应
		this.pageWrap.parent().css("top",this.ul.height()+34);

		return this;
	});

	List.method('renderPage',function(){
		var self = this;
		var listLen = this.listData.length,
			pageNum = Math.ceil( listLen/this.defaultPageNum );	
		//渲染dom
		this.createPageDom(pageNum);

		if ( pageNum > this.defaultPageNum ) {
			this.pageWrap.append('<span>...</span>');
		};

		//给对应的dom绑定事件
		this.pageWrap.off().on('click', 'a', function(e) {
			e.stopPropagation();
			var index = Number($(this).text());
			self.setPage(index);
		});

		$('#page-prev').off().on('click', function(e) {//加off()的原因是，确保每次设置分页数量的时候，让左右箭头不重复绑定事件。
			e.stopPropagation();
			var nowIndex = Number(self.pageWrap.children('a.active').text()), 
				lastIndex = Number(self.pageWrap.children('a:last').text());
			if ( nowIndex - 1 ) {
				if ( lastIndex -  (nowIndex - 1) == self.defaultPageNum) {
					self.createPageDom( pageNum, lastIndex - 1 - self.defaultPageNum ).setPage( nowIndex - 1 ).pageWrap.append('<span>...</span>').prepend('<span>...</span>');
					if ( nowIndex - 1 == 1 ) {
						self.pageWrap.find('span:first').remove();
					};
				}else {
					self.setPage( nowIndex - 1 );
				}
			}

		});

		$('#page-next').off().on('click', function(e) {
			e.stopPropagation();
			var nowIndex = Number(self.pageWrap.children('a.active').text());
			if ( nowIndex + 1 <= self.defaultPageNum && nowIndex + 1 <= pageNum  ) {
				self.setPage(nowIndex+1);
			}else if ( nowIndex + 1 > self.defaultPageNum && nowIndex + 1 <= pageNum ) {
				self.createPageDom( pageNum,nowIndex + 1 - self.defaultPageNum ).setPage( nowIndex + 1 ).pageWrap.append('<span>...</span>').prepend('<span>...</span>');
				if ( nowIndex + 1 == pageNum ) {
					self.pageWrap.find("span:last").remove();
				};
			};
		});

		return this;
	});

	List.method('setPage',function(pageIndex){
		var pIndex = Number(pageIndex);
		var begin = (pIndex-1) * this.defaultPageNum,
			end = pIndex * this.defaultPageNum;

		var indexToData = this.listData.slice(begin, end);
		//根据页码来为a添加active类最简单，不用繁琐的eq计算
		this.pageWrap.children('a:contains("'+pageIndex+'")').addClass('active').siblings().removeClass('active');
		this.renderList(indexToData);

		return this;
	});

	List.method('createPageDom',function(pageNum,count) {
		var arr = [],
			count = count || 0;
		for(var i = 1 + count; i <= pageNum && i <= this.defaultPageNum + count ; i++){
			var aStr = '<a href="javascript:;" >'+i+'</a>'
			arr.push(aStr);
		}
		this.pageWrap.children().remove();
		this.pageWrap.append(arr.join(''));

		return this;
	});

	List.method('setDefaultPageNum',function(num){
		if (!num) {
			alert("请输入不为0的数字");
			return false;
		};
		this.defaultPageNum = num;
		this.pageDestroy().renderPage().setPage(this.defaultPageIndex);
	});

	List.method('pageDestroy',function(){
		this.pageWrap.children().off().remove();
		return this;//返回this.供链式调用
	});

	List.method('request',function(fn,parms){
		fn(parms);
	})

	K.util.dropdownPage = new List();

})(jQuery); 

// $(function(){
// 	var dropdownPage = K.dropdownPage;
// 	var dropdownUl = $(".ui-select1 .dropdown-menu");
// 	var pageNation = $(".ui-select1 .dropdown-page-w .page-index-w"); 
// 	dropdownPage.init(data,dropdownUl,pageNation);
// })