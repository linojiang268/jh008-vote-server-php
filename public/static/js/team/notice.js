$(function(){
	var Class = K.Class,
		BaseView = K.util.BaseView,
		pageManager = K.util.pageManager,
		Observe = K.Observe,
		server = K.server,
		DialogUi = K.dialogUi,
		NoticeWay = K.NoticeWay;

	var Notice = K.Notice;

	var page = {
		initialize: function() {
			var container = $('#noticeCon');
			server.listMembers({
				page: 1,
				size: 10000
			}, function(resp) {
				if (resp.code == 0) {
					var notice = new Notice({
						El: container,
						noticeOriginal: '【' + teamName + '】',
						datas: resp.members,
						sendHandler: function(requests, notice) {
							if (!requests.status) {
								return DialogUi.alert('请选择发送通知方式');
							}

			                if (!requests.content) {
			                    return DialogUi.alert('通知内容不能为空');
			                }

			                if (!requests.to_all && !requests.phones.length) {
			                    return DialogUi.alert('请选择需要发送的人员');
			                }

			                requests.to_all ?  requests.to_all = 1 : requests.to_all = 0;
			                
			                requests.send_way = requests.status;
			                requests._token = $('input[name="_token"]').val();
							var dialog = DialogUi.loading('消息发送中...');
			                server.sendNotice(requests, function(resp) {
			                    dialog.close();
			                    if (resp.code == 0) {
			                        DialogUi.message('消息发送成功');
			                        noticeWay.clear();
			                    } else {
			                        DialogUi.alert(resp.message || '发送失败!');
			                    }
			                });
						}
					});
				} else {	
					DialogUi.alert(resp.message || '获取成员出错了！');
				}
			})
		}
	}

	page.initialize();

/*
    var users = [
	    {id:1, name: 'aaa'},
	    {id:2, name: 'bbb'},
	    {id:3, name: 'ccc'},
	    {id:4, name: 'ddd'}];

    var noticeDialog = new K.NoticeDialog({
    	template: 'notice_dialog_template',
		datas: users,
		sendAjaxMethod: function(requests, next, tip) {
			console.log(requests);
			next();
		}
    });*/

})