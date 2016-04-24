$(function(){
    var util = K.util,
        DialogUi = K.dialogUi,
        server = K.server;

    var pushContent     = $('#pushContent'),
        version         = $('#version'),
        url             = $('#url'),
        androidPlate    = $('#androidPlate'),
        compulsoryFalse = $('#compulsoryFalse'),
        noticeForm      = $('#noticeForm');

    var _token = $('input[name="_token"]').val();

    $('#pushBtn').click(function(e) {
        var result = {};

        $.each(noticeForm.serializeArray(), function(index, item) {
            result[item.name] = item.value;
        });

        if (!result.content) {
            DialogUi.message('推送内容不能为空');
        } else if (result.content.length > 60) {
            DialogUi.message('推送内容不能超过60位');
        } else if (!result.version) {
            DialogUi.message('版本号不能为空');
        } else {
            var dialog = DialogUi.confirm({
                text: '确定要发送此通知吗？',
                okCallback: function() {
                    result._token   = _token;
                    var dialog = DialogUi.loading('消息发送中...');
                    server.upgradePush(result, function(resp) {
                        dialog.close();
                        if (resp.code == 0) {
                            pushContent.val('');
                            version.val('');
                            url.val('');
                            androidPlate.find('label').click();
                            compulsoryFalse.find('label').click();
                            DialogUi.message('推送成功了');
                        } else {
                            DialogUi.message(resp.message || '推送出错了');
                        }
                    });
                }
            })
        }
        e.preventDefault();
    });

})