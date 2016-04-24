$(function() {
    
    var Class = K.Class,
        server = K.server,
        DialogUi = K.dialogUi;

    var Notice = K.Notice;

    var notice = new Notice({
        El: $('#noticeCon'),
        noticeOriginal: '【' + activityName + '】',
        //datas: resp.members,
        sendHandler: function(requests, notice) {
            if (!requests.status) {
                return DialogUi.alert('请选择发送通知方式');
            }

            if (!requests.content) {
                return DialogUi.alert('通知内容不能为空');
            }

            requests.to_all ?  requests.to_all = 1 : requests.to_all = 0;
            
            requests.send_way = requests.status;
            requests._token = $('input[name="_token"]').val();
            requests.activity = activity_id;
            var dialog = DialogUi.loading('消息发送中...');
            server.activitySendNotice(requests, function(resp) {
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

})