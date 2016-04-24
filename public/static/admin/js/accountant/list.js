$(function(){
    var util = K.util,
        DialogUi = K.dialogUi,
        server = K.server,
        ImageUploader = K.imageUploader;
    //var ImageUploader = K.imageUploader;
    //声明配置过滤器
    var Filters = {
        props: {},
        setFilters: function(obj) {
            //每次要清空一下props
            this.initProps();
            $.extend(true, this.props, obj);
        },
        getProps: function(){
            return this.props;
        },
        initProps: function(){
            this.props = {};
        },
        fee: function(fee){//将分转成元
            var yuan = Math.floor(fee/100),
                                jiao = Math.floor((fee%100)/10),
                                fen  = Math.floor((fee%100)%10);
                            console.log(yuan);
                            console.log(jiao);
                            console.log(fen);
                            return yuan+"."+jiao+fen;
        }
    };
    //渲染表格
    var AccountantList = (function() {
        var table = null;
        function renderTablePag() {
            return util.PagTable({
                el: 'tableCon',
                columnNameList: [
                    'index', 
                    'activity_title',
                    'activity_begin_time',
                    'activity_end_time',
                    'team_name',
                    'status_desc',
                    function(data){
                        function fee(fee){
                            
                        }
                        return Filters.fee(data.total_fee) + ' / ' + Filters.fee(data.transfered_fee);
                    },
                    function(data){
                        return '<a href="javascript:;" id="detail" class="button button-orange button-m">查看详情</a>';
                    },
                    function(data){
                        if (data.status == 1) {
                            return '<a id="gotoTransfer" href="javascript:;" class="button button-orange button-m mr10">去转账</a>';
                        }else if (data.status == 2) {
                            return '<a id="sureTransfer" href="javascript:;" class="button button-orange button-m mr10">确认转账</a>';
                        }else {
                            return '无';
                        }
                    }
                ],
                source: function(o, PagTable, option) {
                    var parms = {};
                    parms.page = o.currentPage;
                    $.extend(true, parms, Filters.getProps());
                    console.log(parms);
                    server.listIncome(parms, function(resp){
                        console.log(resp);
                        if (resp.code == 0) {
                            PagTable({totalPage: Math.ceil(resp.total/10), datas: resp.incomes});
                        }else {
                            DialogUi.alert('没有数据');
                        }
                    });
                },
                events: {
                    "click #detail"       : "detailHandler",
                    "click #gotoTransfer" : "gotoTransferHandler",
                    "click #sureTransfer" : "sureTransfer"
                },
                eventsHandler: {
                    detailHandler: function(e, row) {
                        console.log(Filters.props.status);
                        var itemArr = [];
                        if (!row.data.detail.length) {
                            itemArr.push('还没进行过任何转账');
                        }else {
                            $.each(row.data.detail,function(index, el) {
                            var str = '<p style="padding-bottom: 20px;">'+
                                '<div>转账时间：'+el.op_time+'</div>'+
                                '<div>转账金额：'+Filters.fee(el.fee)+'元</div>'+
                                '<div><span style="vertical-align: top;">转账凭证：<img src="'+el.evidence_url+'"></div>'+
                                '</p>';
                                itemArr.push(str);
                            });
                        }
                        
                        //弹出层
                        DialogUi.open({
                            btn:['确认'],
                            title: '转账明细',
                            area: ['715px', '650px'],
                            content : '<div>'+itemArr.join('')+'</div>',
                            closeBtn : 2
                        });
                    },
                    gotoTransferHandler: function(e, row) {
                        //去转账
                        console.log(Filters.props.status);
                        var parms = {
                                'id' : row.data.id,
                                '_token' : $('input[name="_token"]').val()
                            };
                        if (!Filters.props.status) {
                            console.log(parms);
                            server.doIncomeTransfer(parms,function(resp){
                                console.log(resp);
                                if (resp.code == 0) {
                                    row.data.status = 2;
                                    row.data.status_desc = '转账中';
                                    DialogUi.alert(resp.message || '操作成功',function(){
                                        row.refresh();
                                    }); 
                                }
                            });    
                        }else if (Filters.props.status == 1) {}{
                            console.log(parms);
                            server.doIncomeTransfer(parms,function(resp){
                                console.log(resp);
                                if (resp.code == 0) {
                                    DialogUi.alert(resp.message || '操作成功',function(){
                                        row.trigger('destroy');
                                    }); 
                                }
                            });  
                        }
                        
                    },
                    sureTransfer: function(e, row) {
                        console.log(Filters.props.status);
                        //弹窗弹出前先重新初始化pop
                        page.popInit();

                        DialogUi.open({
                            type: 1,
                            btn:['确认已转账','取消'],
                            title: row.data.activity_title,
                            closeBtn: 1,
                            area: ['515px', '350px'],
                            shadeClose: false,
                            content: $('.section-finance-pop'),
                            success: function (layero,index) {
                                console.log(123);
                                //evidenceUpload.render();
                            },
                            yes: function(index){
                                var fee = $('#f-money').val(),
                                    evidence = evidenceUpload.getUrl();

                                if ( !fee || Number( fee ) == NaN ){
                                    DialogUi.alert('请输入数字金额');
                                    return false;
                                }else if(!evidence){
                                    DialogUi.alert('请上传转账凭证照片');
                                    return false;
                                }
                                //fee 提交时的单位为分
                                var parms = {
                                    id : row.data.id,
                                    fee : Number( fee ) * 100,
                                    evidence : evidence,
                                    _token : $('input[name="_token"]').val()
                                };
                                console.log(parms);
                                server.confirmIncomeTransfer(parms,function(resp){
                                    console.log(resp);
                                    if(resp.code == 0){
                                        if (row.data.total_fee ==  row.data.transfered_fee + Number(fee)*100 ) {
                                            row.data.status = 3;
                                            row.data.status_desc = '已转账';
                                            row.data.transfered_fee = Number(fee)*100;
                                            var parms = {
                                                id : row.data.id,
                                                _token : $('input[name="_token"]').val()
                                            }
                                            server.finishIncomeTransfer(parms,function(resp){
                                                if (resp.code == 0) {
                                                    DialogUi.alert(resp.message, function () {
                                                        row.refresh();
                                                    }); 
                                                }else {
                                                    DialogUi.alert(resp.message || '提交失败了', function () {
                                                       window.location.reload(); 
                                                    });
                                                }
                                            });
                                            
                                        }else {
                                            row.data.transfered_fee = row.data.transfered_fee + Number(fee)*100;
                                            DialogUi.alert(resp.message, function () {
                                                row.refresh();
                                            });
                                        }
                                        
                                    }else {
                                        DialogUi.alert(resp.message || '提交失败');
                                    }
                                });
                                layer.close(index); //一般设定yes回调，必须进行手工关闭

                            }
                        });
                        //$('#f-pop').show();
                    }
                }
            })
        }

        function _render() {
            table = renderTablePag();
        }
        function _refresh(obj){
            Filters.setFilters(obj);
            table.refresh();
        }

        return {
            render: _render,
            refresh: _refresh
        }
    })();
    //初始化图片上传插件
    var evidenceUpload = (function(){
        var img_url = null;
        var evidenceEl = $('#f-evidence');

        function createImageUploader() {
            var imageUploader = new ImageUploader({
                _token: $('input[name="_token"]').val(),
                text: '上传转账凭证'
            });
            evidenceEl.append(imageUploader.El);
            imageUploader.on('uploadFinish', function(resp) {
                console.log(resp);
                img_url = resp.options.data.image_url;
            });
            return imageUploader;
        }

        function getImgUrl(){
            return img_url;
        }

        return {
            getUrl: getImgUrl,
            render: createImageUploader
        }
    })();
    //页面page对象
    var page = {
        initialize: function(){
            AccountantList.render();
            evidenceUpload.render();
            //this.evidence = evidenceUpload.render();
            this.init();
        },
        init: function(){//用于页面初始化，主要初始化导航胶囊的点击切换事件。
            var self = this;
            $(".filter-bar").on('click', 'dd', function(event) {
                var obj = {},
                    v = $(this).children().data('v');
                if (v) {
                    obj.status = Number(v);
                }
                $(this).addClass('filter-select').siblings('dd').removeClass('filter-select');
                console.log(obj);
                self.tableTab(obj);
            });
            $('#activitySearch-btn').on('click', function(event) {
                var begin_time = $('#date-start').val(),
                    end_time = $('#date-end').val(),
                    v = $(".filter-select").data('v');
                var obj = {};
                obj.begin_time = begin_time;
                obj.end_time = end_time;
                if (v) {
                    obj.status = Number(v);
                }
                console.log(obj);
                self.tableTab(obj);
            });
        },
        popInit: function () {
            console.log("asdsadsa");
            $('#f-money').val('');
            //if ($('#f-evidence .close')) {
            //    $('#f-evidence .close').trigger('click');
            //}
            //$('#f-pop').show();
            //$('#f-pop').find('.close').trigger('click');

        },
        tableTab: function(obj){
            AccountantList.refresh(obj);
        }
    };

    page.initialize();
});

$(function(){
    //初始化日期控件
    $('.date-w').on('click', 'input', function(event) {
            event.preventDefault();
            laydate({istime: true, format: 'YYYY-MM-DD hh:mm:ss'});
    });
});
