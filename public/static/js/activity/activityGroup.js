Function.prototype.method = function(name,fn) {
		this.prototype[name] = fn;
		return this;
};
(function($,window){
	/**
	 * @description : 组对象
	 * 
	 */
	var Group = function(data){
		this.G ="";                 //组别[A,B,C]
		this.id = "";               
		this.aid = ""; 
		this.dom = "";             //活动id 
		this.members = [];          //成员

		this.init(data);
	};

	Group.method('init',function(data){
		//data为group中的一组数据
		this.id = data.id;
		this.aid = data.activity_id;
		this.G = data.name;
		this.members = data["members"];//这是一个数组
		//初始化group，渲染dom
		var self = this;
		var len = this.members.length;
		//创建dom
		this.createDom();
        //渲染member ,forEach对IE8及IE8以下不支持，要考虑兼容就用for
		// this.members.forEach(function(n,i){
		// 	self.renderMember(new Member(n).getDom());
		// });
		for (var i = 0, len = this.members.length; i < len; i++) {
			self.renderMember(new Member(this.members[i]).getDom());
		};
		//渲染页面
		this.render();
	});
	//渲染成员
	Group.method('renderMember',function(member){
		this.dom.find('.fz-r').append(member);
	});
	//渲染组
	Group.method('render',function(){
		$(".fz-div-w").append(this.dom);
		this.dom.data("g",this);//对象附加到dom data上
		if ( !this.dom.find('.fz-r a').length ) {
			this.dom.find('.fz-del-btn').removeClass('hide');
		}
	});
	//创建组dom
	Group.method('createDom',function() {
		var dom =  '<div class="fz-div" id="g'+this.id+'">'+
	                   '<div class="fz-h">'+
	                       '<span class="fz-th">'+this.G+'</span>'+
	                       '<span> (<em>'+this.members.length+'</em>人)</span>'+ 
	                       '<button class="fz-del-btn hide">删除</button>'+ 
	                   '</div>'+
                  	   '<div class="fz-r">'+
                      	   '<i></i>'+
                  	   '</div>'+
                	'</div>';
        this.dom = $(dom);
	});
	//删除成员
	Group.method('delMember',function(id) {
		var i = this.dom.find(".fz-h em"),
			num = Number(i.text());
			num -=1;
		if (!num) {
			this.dom.find(".fz-del-btn").removeClass('hide');
		}
		i.text(num);
		this.dom.find("#m"+id).remove();
		this.dom.height(this.dom.find('.fz-r').height()+70);
	});
	//增加成员
	Group.method('addMember',function(id){
		var i = this.dom.find(".fz-h em"),
			num = Number(i.text());
			num +=1;
		if (num) {
			this.dom.find(".fz-del-btn").addClass('hide');
		}
		i.text(num);
		$("#m"+id).data("gid",this.id);//新增成员把gid改成对应组别的gid
		this.dom.find(".fz-r").append($("#m"+id));
	});

	/**
	 *@description : 成员对象
	 *
	 */
	var Member = function(data){
		this.id = data.id;
	    this.uid = data.user_id;      //user_id
		this.aid = data.activity_id;  //活动id
		this.gid = data.group_id;     //组id
		this.name = data.name;		  //user_name
		this.user = data.user;		  //user 信息

		this.init( this.name, this.uid, this.id, this.user);
	};

	Member.method('init',function( name, uid, id, user ){

		var data = {};

		data.name = name;
		data.uid = uid;
		data.id = id;
		data.user = user || {avatar_url:"/static/images/activity/user.png"};

		this.dom = this.render( data );
	});

	Member.method('render',function( data ){

		var dom = $('<a class="fz-ren" href="javascript:;" id="m'+data.id+'" data-uid='+data.uid+' title="点击调换分组">'+
                        '<div class="user-h-w">'+
                          '<img class="user-HeadPortrait" src="'+data.user.avatar_url+'" alt="user">'+
                          '<img class="user-mask" src="/static/images/activity/hover.png" alt="mask">'+
                        '</div>'+
                        '<span class="user-name">'+data.name+'</span></a>');
			
			// dom.data("info",data.user);
			dom.data("id",this.id);
			dom.data("aid",this.aid);
			dom.data("gid",this.gid);

		return dom;
	});
	//获取成员对象dom
	Member.method('getDom',function(){
		return this.dom;
	});

	window.Group = Group;

})(jQuery,window)

$(function(){
	//库引用
   	var Dialog = K.dialogUi,
   	    Server = K.server;

   	var groupData;
   	var token = $("input[name='_token']").val();
   	var param = {
   			"activity_id":activity_id
   	};
   	//设置加载动画
	Dialog.loading("正在请求，请稍等。");
   	Server.getGroups(param,function(data){
   		//关闭加载动画
   		Dialog.loading().close();
   		if (data.code == 0) {
   			$("#user-num").text(data.total);
   			if ( data.grouped.length ) {
   				//渲染组 
   				groupData = data.grouped;
   				$.each(data.grouped,function(index, el) {
   				    var G = new Group(el);
   				});
   			};
   		}else {
   			Dialog.alert(data.message);
   		}
   	});

   //分组按钮事件
   $(".fqdd").on('click', function(event) {
   		event.preventDefault();
   		var groups = $("#groups-input").val(),
   			parm = {
   				"activity_id": activity_id,
   				"groups": groups,
   				"_token" : token
   			};
   		//调用分组接口
   		Server.createGroups(parm,function(data){
   			if (data.code == 0) {
   				Dialog.alert(data.message,function(){
   					window.location.reload();
   				});
   			}else {
   				Dialog.alert(data.message);
   			}
   		});
   });

   //删除组按钮事件
   $(".fz-div-w").on('click', '.fz-del-btn', function(event) {
   		event.preventDefault();
   		//设置加载动画
		Dialog.loading("正在请求，请稍等。");
   		var self = $(this),
   			group_id = $(this).parents(".fz-div").attr("id").slice(1),
   			parm = {
   				"activity_id" : activity_id,
   				"group_id" : group_id,
   				"_token" : token
   			};
   			
   		Server.deleteGroup(parm,function(data) {
   			if ( data.code == 0 ) {
   				Dialog.alert(data.message,function() {

   					self.parents(".fz-div").addClass("reset").width(0).height(0);
   					//根据id，在groupData删除对应的组
   					$.each( groupData,function(index, el) {
   						if ( el && el["id"] == group_id ) {
   							groupData.splice(index, 1);	
   							return;
   						}
   					});

   				});
   			}else {
   				Dialog.alert(data.message);
   				return;
   			}
   		});
   });

   //绑定more事件
   $(".fz-div-w").on('click', 'i', function(event) {
   		//标题头的高为60px
   		var parent1 = $(this).parents('.fz-div');

   		if ( parent1.hasClass('open') ) {
   			parent1.removeClass('open').height(150);
   		}else {
   			parent1.addClass('open').height($(this).parent().height()+70).siblings().removeClass('open').height(150);
   		}
   });

   //组员选调分组事件绑定
   $(".fz-div-w").on('click',".fz-ren", function(event) {

		event.preventDefault();
		event.stopPropagation();

		var id = $(this).data("id"),
		    aid = $(this).data("aid"),//activity id
		    gid = $(this).data("gid"),//group id
		    parentObj = $(this).parents(".fz-div").data("g");//所在组对象
		    
		var height_p = $(this).parent().height();

		var gArr = [];
		    $.each(groupData,function(index, el) {
		    	var g = {};
		    	if ( el["id"] == gid ) {return;}
		    	g["id"] = el["id"];
   				g["value"] = el["name"];
				(function(o){
   	  				return function(){
   	  					gArr.push(o);
   	  				}();
   	  			})(g);
		    }); 
		
		Dialog.radio({
			title: "请选择分组",
			datas: gArr,
			callback:function(value,close){
				// value 数据结构 {id:xx,value: xx}
				var parm = {
					"activity_id": activity_id,
					"member_ids" : id,
					"group_id"   : value.id,//目标组id
					"_token"     : token
				};
				Server.setGroup(parm,function(data) {
					if ( data.code == 0 ) {
						var toGroup = $("#g"+value.id),//目标组
					    	toParentObj = toGroup.data("g");//目标组对象

						toParentObj.addMember(id);//在目标组增加成员
						parentObj.delMember(id);//在当前组删除成员

						toGroup.addClass('open').height(toGroup.find('.fz-r').height()+70);
						//关闭分组弹窗
						close();
					}else {
						//关闭分组弹窗
						close();
						Dialog.alert(data.message);
					}
				});
			}
		});
	});

});
