$(function() {
    // 签到管理页

    var Server = K.server,
        Util   = K.util,
        Observe = K.Observe,
        Dialog = K.dialogUi;

   var tabManager = lc.tabs('#tabs');

    var _token = $('input[name="_token"]').val();

    function tip(el, text) {
        return Dialog.tip(el, text);
    }

    function message(text) {
        return Dialog.message(text);
    }   

    function alert(text) {
        return Dialog.alert(text);
    }

    function loading(text) {
        return Dialog.loading(text);
    }

    function makeRoute(name) {
        location.hash = name;
    }

    tabManager.make('qrcode', function() {
        makeRoute('qrcode_hash');
        return function() {
            //
        }
    });

    tabManager.make('list', function() {
        makeRoute('list_hash');
        var table;
        return function() {
            if (table) {
                table.refresh();
            }

            if (!table && qr_code_id) {
                table = Util.PagTable({
                    el: 'tableCon',
                    columnNameList: [
                        'index', 
                        'nick_name',
                        'mobile',
                        'created_at',
                    ],
                    source:[],
                    source: function(o, PagTable, option) {
                        var parms = {};
                        parms.activity_id = activity_id;
                        parms.size = 20;
                        // if (option.refresh && option.refresh[0]) {
                        //     parms.step = option.refresh[0].qr_code_id;
                        // } else {
                        //     parms.step = qr_code_id;
                        // }
                        // 现在只有1个签到步骤，所有step 暂时写死为 1
                        parms.step = 1 ;
                        var dialog = loading('正在加载中');
                        Server.listMembersSignQrcode(parms, function(resp){
                            dialog.close();
                            if (resp.code == 0) {
                                PagTable({totalPage: Math.ceil(resp.total/20), datas: resp.check_ins});
                            } else {
                                alert(resp.message || '查询数据列表出错');
                            }
                        })
                    }
                }); 
            }
        }
    });

    var page = {
        initialize: function() {
            var hash = location.hash ? location.hash.split('#')[1] : 'qrcode_hash';
            tabManager.select(hash.split('_')[0]);
        }
    }

    page.initialize();

})