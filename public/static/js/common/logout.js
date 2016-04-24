$(function(){
	var Server = K.server,
		Dialog = K.DialogUi;
	$(".head-nav").off().on('click', '.logout', function() {
		Server.logout({},function(resp){
			if (resp.code == 0) {
				window.location.href = '/community';
				return true;
			}else {
				Dialog.alert(resp.message || "退出失败");
				return false;
			}
		});
		return false;
	});
})