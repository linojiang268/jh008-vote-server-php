$(function() {

var Observe = K.Observe,
    server = K.server,
    DialogUi = K.dialogUi,
    MapHelper = K.mapHelper;

var util = {
    extendStepData: function(templateObject, datas) {
        var result = {};
        for (var key in templateObject) {
            if (datas[key]) {
                result[key] = datas[key];
            } else {
                result[key] = templateObject[key];
            }
        }
        return result;
    },
    setLaydate: function(options) {
        var defaultOptions = $.extend({}, {
            format: 'YYYY-MM-DD hh:mm:ss',
            istime: true,
            event: 'click',
            fixed: false
        }, options || {});
        return laydate(defaultOptions);
    },
    formateDate: function (date) {
        var date  = date || new Date();
        var year  = date.getFullYear();
        var month = date.getMonth() + 1;
        var day   = date.getDate();
        //var hour  = date.getHours();
        //var minute= date.getMinutes();
        //var second= date.getSeconds();
        return year + "-" + month + '-' + day;
    }
}

var ActiveImg = function(options) {
    this.data = options;
    this.init();
}

ActiveImg.prototype = {
    constructor: ActiveImg,
    init: function() {
        var data = this.data;
        this.El = $('<div class="ui-uploadThumb ui-uploadThumb-has"></div>');
        this.render();
        this.events();
    },
    render: function() {
        var data = this.data;
        var html = '<div class="ui-uploadThumb-link" href="javascript:;">' +
                        '<div class="ui-upload-img-wrap">' +
                            '<img src="'+ data.url +'" alt="">' +
                        '</div> ' +
                        '<div class="ui-uploadThumb-option">' +
                            '<div class="setting">' +
                                (data.cover ? '<a href="javascript:;" id="cancelCover" class="button button-m button-blue">取消封面</a>' : 
                                '<a href="javascript:;" id="setCover" class="button button-m button-b-blue">设为封面</a>') +
                            '</div>' +
                            '<a href="javascript:;" class="close"><i class="icon iconfont"></i></a>' +
                        '</div>' +   
                        (data.cover ? '<span class="show-text">封面</span>' : '') +         
                    '</div>';
        this.El.html(html);
    },
    events: function() {
        var _this = this,
            imgClose = _this.El.find('.close');
        this.El.on('click', '.close', function() {
            _this.close();
            _this.trigger('close', _this);
        }).on('click', '#setCover', function(e) {
            $(e.target).replaceWith('<a href="javascript:;" id="cancelCover" class="button button-m button-blue">取消封面</a>');
            _this.data.cover = true;
            _this.render();
            _this.trigger('setCover', _this);
        }).on('click', '#cancelCover', function(e) {
            $(e.target).replaceWith('<a href="javascript:;" id="setCover" class="button button-m button-b-blue">设为封面</a>');
            _this.data.cover = false;
            _this.render();
            _this.trigger('cancelCover', _this);
        })
    },
    close: function() {
        this.El.remove();
    }
}

Observe.make(ActiveImg.prototype);

// step nav
var navStep = function() {
    var step = null,
        el = $('.ui-step');

    function render(step) {
        var activeItem;
        if ((activeItem = el.find('.ui-step-item-active')).length) {
            activeItem.removeClass('ui-step-item-active')
        }
        el.find('.ui-step-item').eq(step-1).addClass('ui-step-item-active');
    }

    return {
        setStep: function(stepIndex) {
            if (step != stepIndex) {
                render(stepIndex);
            }
            step = stepIndex;
        }
    }
}()

// step manager
var StepManager = (function() {
    var list = [], currentIndex;

    function isUnique(id) {
        var flag = true;
        $.each(list, function(i, step) {
            if (step.key == id) {
                flag = false;
                return false;
            }
        })
        return flag;
    }

    function _make(id, initDataFn, fn) {
        if (isUnique(id)) {
            var step = new Step(id, initDataFn, fn);
            list.push(step);
        }
    }

    function _go(index) {
        if (index > list.length || index <= 0 || $.type(index) != 'number') {
            throw Error('wrong index');
        } else {
            currentIndex = index;
            for (var i = 0; i < list.length; i++) {
                index == i + 1 ? (function() {
                    list[i].show().initialize();
                    navStep.setStep(index);
                }()) : list[i].hide();
            }            
        }
    }

    function _next() {
        _go(currentIndex + 1);
    }

    function _back() {
        _go(currentIndex - 1);
    }

    function _getData() {
        var result = {};
        for (var i = 0; i < list.length; i++) {
            $.extend(result, list[i].getData());
        }
        return result;
    }

    function _initData(datas) {
        for (var i = 0; i < list.length; i++) {
            list[i].initData(datas);
        }
    }

    return {
        make: _make,
        next: _next,
        back: _back,
        go: _go,
        getData: _getData,
        initData: _initData
    }
})()

/**
 * Step
 * @params {String}       element id
 * @params {Function}     initialize function  only excute once
 *
 */
var Step = function(id, initDataFn, fn) {
    this.key = id;
    this.El = $('#' + id);
    this.initFlag = false;
    this.initializeCallback = fn;
    this.initDataFn = initDataFn;
}

Step.prototype = {
    constructor: Step,
    initialize: function() {
        if (!this.initFlag) {
            var that = this;
            setTimeout(function() {
                that.initializeCallback && that.initializeCallback.call(that);
            })
            this.initFlag = true;
        }
        return this;
    },
    initData: function() {
        this.initDataFn && (
            this.initDataFn.call(this)
        )
    },
    show: function() {
        this.El.show();
        return this;
    },
    hide: function() {
        this.El.hide();
    },
    back: function() {
        StepManager.back();
    },
    next: function() {
        StepManager.next();
    },
    getInputData: function() {
        var  _this = this;
        if (this.inputData) {
            $.each(this.inputData, function(i, key) {
                var el = _this.El.find('#' + key);
                if (el.length) {
                    _this.data[key] = el.val().trim();
                }                
            })
        }
    },
    setInputDataToHtml: function() {
        var _this = this;
        if (!this.inputData || $.type(this.inputData) != 'array') 
            return false;
        $.each(this.inputData, function(i, key) {
            var el = _this.El.find('#' + key);
            if (el.length) {
                el.val(_this.data[key]);
            }
        })
    },
    setData: function(data) {
        var _this = this;
        if (data && $.type(data) == 'object') {
            $.each(data, function(key, val) {
                _this.data[key] = val;
            })
        }
    },
    getData: function() {
        return this.data;
    },
    setInputData: function(inputData) {
        this.inputData = inputData;
    }
}

// preview dialog 
var previewDialog = function(datas, successCallback) {
    var contentString = '<div class="activity_confirmation_box" class="activity_sure_box">' +
        '<div class="activity_confirmation_box_tity">' +
            '<p class="activity_confirmation_box_tity_t">活动发布信息总览</p>' +
            '<p class="activity_confirmation_box_tity_c"></p>' +
        '</div>' +
        '<div class="activity_confirmation_text">' +
            '<table>' +
                '<tr>' +
                    '<td style="width: 80px; text-align:left">活动名称：</td>' +
                    '<td>'+ datas.title +'</td>' +
                '</tr>' +
                '<tr>' +
                    '<td>活动时间：</td>' +
                    '<td>'+ datas.begin_time + '-' + datas.end_time +'</td>' +
                '</tr>' +
                '<tr>' +
                    '<td>联系人：</td>' +
                    '<td>' + datas.contact + '</td>' +
                '</tr>' +
                '<tr>' +
                    '<td>联系电话：</td>' +
                    '<td>' + datas.telephone + '</td>' +
                '</tr>' +
                '<tr>' +
                    '<td>活动地点：</td>' +
                    '<td>' + datas.address + '</td>' +
                '</tr>' +
                '<tr>' +
                    '<td>报名时间：</td>' +
                    '<td>' + datas.enroll_begin_time + '-' + datas.enroll_end_time + '</td>' +
                '</tr>' +
                '<tr>' +
                    '<td>是否审核：</td>' +
                    '<td>' + (datas.auditing == 1 ? '审核' : datas.auditing == 0 ? '不审核' : '') + '</td>' +
                '</tr>' +
                /*'<tr>' +
                    '<td>人员属性：</td>' +
                    '<td>' + ((datas.enroll_type == 1 && '任何人都可以参加') || (datas.enroll_type == 2 && '仅社团成员参加')) + '</td>' +
                '</tr>' +*/
                '<tr>' +
                    '<td>人数：</td>' +
                    '<td>' + (datas.enroll_limit > 0 ? datas.enroll_limit + '人' : '不限') + '</td>' +
                '</tr>' +
                '<tr>' +
                    '<td>报名费：</td>' +
                    '<td>' + (datas.enroll_fee_type == 1 ? '免费' : datas.enroll_fee_type == 2 ? 'AA制' : datas.enroll_fee_type == 3 ? datas.enroll_fee + '元' : '') + '</td>' +
                '</tr>' +
                '<tr>' +
                    '<td>报名资料：</td>' +
                    '<td>' + (JSON.parse(datas.enroll_attrs).join('、')) + '</td>' +
                '</tr>' +
            '</table>' +
        '</div>' +
        '<div class="activity_confirmation_ts">' +
            '<p class="activity_confirmation_ts_i"><i class="icon iconfont" style="font-size:35px;">&#xe60a;</i></p>' +
            '<p class="activity_confirmation_ts_xx">以上信息请仔细核对，<br/>活动一旦发布，将不能修改！</p>' +
        '</div>' +
        '<div class="activity_confirmation_qr">' +
            '<a href="javascript:void(0);" id="edit" class="button button-blue cancel-btn"> 返回修改 </a>&nbsp;' +
            '<a href="javascript:void(0);" id="submit" class="button button-orange ok-btn ml10"> 确定提交 </a>' +
       ' </div>' +
    '</div>';
    var flag = false;
    layer.open({
        type: 1,
        title: false,
        closeBtn: false,
        area: ['465px', '555px'],
        shadeClose: true,
        content: contentString,
        success: function(layero, index) {
            function close() {
                layer.close(index);
            }
            layero.find('#edit').click(function() {
                close();
            })
            layero.find('#submit').click(function() {
                if (flag) return;
                flag = true;
                var dialog = DialogUi.loading('活动发布中...');
                server.publishActivity({activity: page.aid, _token: page._token}, function(resp) {
                    dialog.close();
                    flag = false;
                    if (resp.code == 0) {
                        successCallback && successCallback(close);
                    } else {
                        DialogUi.alert(resp.message || '活动发布失败')
                    }
                })
            })
        }
    });
}

// create activity ajax request
var createActivity = function(datas, callback) {
    var dialog = DialogUi.loading('操作中');
    var datas = $.extend(datas || {}, {_token: page._token});
    server.createActivity(datas, function(resp) {
        dialog.close();
        if (resp.code == 0) {
            callback(resp.id);
        } else {
            DialogUi.alert(resp.message || '服务器出错了!');
        }
    })
}

// update activity ajax request
var updateActivity = function(datas, callback) {
    var dialog = DialogUi.loading('操作中');
    var datas = $.extend(datas || {}, {_token: page._token});
    if (page.aid) {
        datas.id = page.aid;
    }
    server.updateActivity(datas, function(resp) {
        dialog.close();
        if (resp.code == 0) {
            callback();
        } else {
            DialogUi.alert(resp.message || '服务器出错了!');
        }
    })
}

// basic infor
var BasicStep = StepManager.make('baseInfor', function() {
    this.data = util.extendStepData({
        title: '',
        begin_time: '',
        end_time: '',
        contact: '',
        telephone: '',
        cover_url: '',
        images_url: ''
    }, page.data);
}, function() {
    var _this = this;
    var data = this.data;

    this.setData({update_step: 2}); // 当前更新步骤为第二步
    this.setInputData(['title', 'begin_time', 'end_time', 'contact', 'telephone']);
    this.setInputDataToHtml();

    var startLaydate, endLaydate, nowDate = util.formateDate(new Date()) + ' 00:00:00';

    function setStartLaydate(o) {
        var options = $.extend({
            elem: '#begin_time',
            choose: function(dates) {
                setEndLaydate({min: dates});
            }
        }, o);

        util.setLaydate(options);
    }

    function setEndLaydate(o) {
        var options = $.extend({
            elem: '#end_time'
        }, o);
        util.setLaydate(options);
    }

    setTimeout(function() {
        setStartLaydate({
            min: nowDate
        });
        setEndLaydate({
            min: nowDate
        });
    }, 100);

    // 初始化图片上传 webuploader
    function setImageUploader() {
        var uploader = WebUploader.create({
            formData: {_token: page._token},
            fileVal: 'image',
            auto: true,
            swf: '/static/plugins/webuploader/Uploader.swf',
            server: '/community/image/tmp/upload',
            pick: '.ui-uploadThumb-icon',
            accept: {
                title: 'Images',
                extensions: 'gif,jpg,jpeg,bmp,png',
                mimeTypes: 'image/*'
            }
        })

        uploader.on('uploadSuccess', function(file, resp) {
            if (resp.code == 0) {
                var curData = {url: resp.image_url};
                createActiveImg(curData);
                // data.imgs.push(curData);            
            } else {
                DialogUi.alert(resp.message || '上传失败');
            }
        })

        uploader.on('uploadError', function(file) {
            DialogUi.alert('上传失败');
        })        
    }
    setImageUploader();

    function setImagesUriToData() {
        var result = [];
        $.each(activeImgs, function(i, activeImg) {
            result.push(activeImg.data.url);
        })
        data.images_url = JSON.stringify(result);
    }

    var activeImgs = [];
    function createActiveImg(imgData) {
        if (activeImgs.length >= 4) {
            DialogUi.alert('至多上传4张活动照片');
            return;
        }

        var activeImg = new ActiveImg(imgData);

        _this.El.find('#uploadSelector').before(activeImg.El);

        activeImg.on('setCover', function(ai) {
            data.cover_url = ai.data.url;
            for (var i = 0; i < activeImgs.length; i++) {
                if (activeImg != activeImgs[i]) {
                    activeImgs[i].data.cover = false;
                    activeImgs[i].render();
                }
            } 
        })

        activeImg.on('cancelCover', function(ai) {
            data.cover_url = '';
        })

        activeImg.on('close', function(ai) {
            if (data.cover_url == ai.data.url) {
                data.cover_url = '';
            }
            for (var i = activeImgs.length - 1; i >=0; i--) {
                if (activeImgs[i].data.url == ai.data.url) {
                    activeImgs.splice(i, 1);
                }
            }
            if (activeImgs.length < 4) {
               $('#uploadSelector').show(); 
            }
        })    
        activeImgs.push(activeImg);
        if (activeImgs.length >= 4) {
            $('#uploadSelector').hide();
        }  
    }

    // init imgs
    if (data.images_url) {
        var images;
        try {
            images = JSON.parse(data.images_url);
            if (images.length >= 4) {
                _this.El.find('#uploadSelector').hide();
            }
            $.each(images, function(i, img_url) {
                var img = {};
                img.url = img_url;
                if (data.cover_url == img_url) {
                    img.cover = true;
                }
                createActiveImg(img);
            })            
        } catch (e) {

        }
    }

    this.El.find('#baseInforForm').validate({
        rules: {
            /*title: {
                required: true,
                maxlength: 22
            },
            begin_time: 'isTime',
            end_time: 'isTime',
            contact: 'required',
            telephone: 'required'*/
        },
        messages: {
            title: {
                required: '活动名称不能为空',
                maxlength: $.format('活动名称不能超过{0}位')
            },
            contact: '联系人不能为空',
            telephone: '联系电话不能为空'
        },
        keyup: false,
        submitHandler: function() {
            _this.getInputData();
            var curData = _this.getData();
            if (new Date(curData.begin_time).getTime() > new Date(curData.end_time).getTime()) {
                return DialogUi.alert('活动开始时间要早于活动结束时间');
            }
            if (activeImgs.length <= 0) {
                return DialogUi.alert('你还没有上传活动图片');
            }
            if (!data.cover_url) { 
                // 没有手动设置封面，自动设置第一张为封面
                data.cover_url = activeImgs[0].data.url;
            }
            setImagesUriToData();
            //console.log(_this.getData());
            if (page.status == 'create') {
                createActivity(_this.getData(), function(activity) {
                    page.aid = activity;
                    page.data.title = _this.getData().title;
                    page.data.cover_url = _this.getData().cover_url;
                    _this.next();
                })                    
            } else if (page.status == 'update') {
                updateActivity(_this.getData(), function() {
                    page.data.title = _this.getData().title;
                    page.data.cover_url = _this.getData().cover_url;
                    _this.next();
                })
            }
        }
    })

})

// address and route
var AddressStep = StepManager.make('addressStep', function() {
    this.data = util.extendStepData({
        address: '',
        brief_address: '',
        location: '',
        roadmap: ''
    }, page.data);
}, function() {
    var _this = this, addressComponentsString, location;

    function setMapSelectAddress(value) { // 地图获取的位置
        value && (_this.El.find('#addressSupplement').html('<span class="address">' + value + '</span>'));
        addressComponentsString = value;
    }

    function setAddress(value) { // 补充活动详细地址
        _this.El.find('#address').val(value);
    }

    var data = this.data;

    this.setData({update_step: 3}); // 当前更新步骤为第三步

    this.setInputData(['brief_address']);
    this.setInputDataToHtml();

    // init map
    var initAddressMap = (function() {
        var click_marker = new BMap.Marker(),
            addressGeoc = new BMap.Geocoder(),
            addressMap = new BMap.Map("addressMap");

        function setPos(lat, lng, province, city, district, street, streetNumber) {
            location = [lat, lng];
            var value = (province == city ? '' : province) + city + district + street + streetNumber;
            setMapSelectAddress(value);
        }

        function pos_getLocation(e) {
            addressGeoc.getLocation(e.point, function (rs) {
                var addComp = rs.addressComponents;
                setPos(e.point.lat, e.point.lng, addComp.province, addComp.city, addComp.district, addComp.street, addComp.streetNumber);
            });
        }

        function addOverlayToMap(point) {
            click_marker.setPosition(point);
            addressMap.addOverlay(click_marker);
        }

        var point; // 初始化地址
        if (data.location && data.location.length > 0) {
            location = data.location;
            point = new BMap.Point(data.location[1], data.location[0]);
        } else {
            point = new BMap.Point(104.073516, 30.653854); // 默认中心点指向北京中心
        }
        addressMap.centerAndZoom(point, 12);
        addressMap.enableScrollWheelZoom(); // 滚轮缩放

        addressLocal = new BMap.LocalSearch(addressMap, {
            renderOptions: {map: addressMap},
            onMarkersSet: function (pois) {
                for (var i = 0; i < pois.length; i++) {
                    pois[i].marker.addEventListener('click', function (e) {
                        pos_getLocation(e.target);
                    })
                }
            }
        })

        setTimeout(function() {
            addOverlayToMap(point);
        }, 800)

        addressMap.addEventListener("click", function (e) {
            if (!e.overlay) {
                addOverlayToMap(e.point);
                pos_getLocation(e);
            }
        })

        $("#addressSearch").click(function () {
            if ($("#search").val()) {
                addressMap.clearOverlays();
                addressLocal.search($("#search").val());
            }
        })
    })()

    var roadHelper;
    var initRoadMap = (function() {
        var init = false,
            El = $('.luxian');
        return function(showFlag) {
            showFlag ? El.show() : El.hide();
            if (!init) {
                roadMap = new BMap.Map("roudMap");
                roadMap.centerAndZoom("成都", 12);
                roadMap.enableScrollWheelZoom();
                roadHelper = new MapHelper.DrawLines(roadMap);
                roadMap.addEventListener('click', function (e) {
                    if (!e.overlay) {
                        roadHelper.addMarker(e.point);
                    }
                })

                roadMap.addEventListener('rightclick', function (e) {
                    if (!e.overlay) { //点击到覆盖物后不做处理
                        roadHelper.removeMarker();
                    }
                })

                roadLocal = new BMap.LocalSearch(roadMap, {
                    renderOptions: {map: roadMap},
                    onMarkersSet: function (pois) { //成功以后回调
                        for (var i = 0; i < pois.length; i++) {
                            pois[i].marker.addEventListener('click', function (e) {
                                roadMap.clearOverlays();
                                roadHelper.addMarker(e.target.point);
                                //console.log(e.target.point);
                            })
                        }
                    }
                })

                $("#roadSearch").click(function () {
                    var value = $("#poiSearch").val().trim();
                    if (value) {
                        roadMap.clearOverlays();
                        roadHelper.markers = [];
                        roadHelper.points = [];
                        roadHelper.draw();
                        roadLocal.search(value);
                    }
                })

                if (data.roadmap) {
                    try {
                        var roadmap = data.roadmap;
                        for (var i = 0; i < roadmap.length; i++) {
                            var point = new BMap.Point(roadmap[i][1], roadmap[i][0]);
                            roadHelper.addMarker(point);
                        }
                        roadHelper.draw();                        
                    } catch (e) {

                    }
                }
            }
        }
    })()

    function getStringifyLinePoints() {
        var result = [];
        if (roadHelper && roadHelper.points) {
            $.each(roadHelper.points, function(i, point) {
                result.push([point.lat, point.lng]);
            })
            return JSON.stringify(result || '');
        }
        return '';
    }

    this.El.find('#addressForm').validate({
        rules: {
            brief_address: 'required',
        },
        messages: {
            brief_address: '地址别名不能为空'
        },
        keyup: false,
        submitHandler: function() {
            if (!addressComponentsString) {
                DialogUi.alert('你必须通过点击地图获取位置');
                return false;
            }
            _this.getInputData();
            var address = addressComponentsString + _this.El.find('#address').val().trim();
            var needUpdateData = {};
            needUpdateData.roadmap = getStringifyLinePoints();
            needUpdateData.address = address;
            location && (needUpdateData.location = JSON.stringify(location));
            _this.setData(needUpdateData);
            updateActivity(_this.getData(), function() {
                _this.next();
            })
        }
    })

    // road input[type=checkbox] btn
    this.El.find('#use-poi').click(function (e) { mm = this;
        $(this).is(':checked') == true ?  initRoadMap(true) : initRoadMap(false);
    })

    // back btn
    this.El.find('#preStepBtn').click(function() {
        _this.back();
    })

    // initalize
    if (data.address) {
        var addressArr = data.address.split(';');
        if (addressArr.length >= 2) {
            setMapSelectAddress(addressArr[0]);
            setAddress(addressArr[1]);
        }
    }

    if (data.roadmap) {
        this.El.find('#use-poi').attr('checked', true);
        initRoadMap(true);
    }
})

// detail 
var DetailStep = StepManager.make('detailStep', function() {
    this.data = util.extendStepData({
        detail: ''
    }, page.data);
}, function() {
    var _this = this;
    var data = this.data;

    this.setData({update_step: 4}); // 当前更新步骤为第四步

    // init u-editor
    ue = UE.getEditor('container', {
        toolbars: [
            ['undo', 'redo', 'bold', 'italic', 'underline', 'fontborder', 'fontfamily', 'fontsize', 'strikethrough', 'removeformat', '|', 'forecolor', 'backcolor', 'cleardoc', 'preview']
        ],
        elementPathEnabled: false,
        maximumWords: 5000,
        initialFrameHeight: 200
    });

    this.El.find('#preStepBtn').click(function() {
        _this.back();
    })

    if (data.detail) { // 设置默认值
        ue.ready(function() { 
            ue.setContent(data.detail);
        })
    }

    var errorTip = $('.detail-error-tip');
    this.El.find('#nextStepBtn').click(function() {
        var contentLength = ue.getContentLength();
        if (contentLength > 50000 || contentLength < 100) {
            errorTip.html('<i class="icon iconfont mr5"></i>活动详情内容要在100字到50000字左右!');
        } else {
            data.detail = ue.getContent();
            errorTip.html('')
            updateActivity(_this.getData(), function() {
                _this.next();
            })
        }
    })

})

// registration requirements
var RegistrationStep = StepManager.make('registrationStep', function() {
    this.data = util.extendStepData({
        enroll_begin_time: '',
        enroll_end_time: '',
        enroll_type: 1, // 1 anyone   2 team    3 secret
        enroll_limit: 0, // numbers  0  >0   
        enroll_fee_type: 1, // 1 mianfei  2  aa   3 shoufei
        enroll_fee: 0, // how mony
        enroll_attrs: '',
        auditing: 0 // 1 shenhe 0 bushenhe
    }, page.data);
}, function() {
    var _this = this, newEnrollAttrs = [], defaultAttrs = ['手机号', '姓名'];
    var data = this.data;

    this.setData({update_step: 5}); // 当前更新步骤为第五步
    this.setInputData(['enroll_begin_time', 'enroll_end_time']);
    this.setInputDataToHtml();
    
    // set date
    function setStartLaydate(o) {
        var options = $.extend({
            elem: '#enroll_begin_time',
            choose: function(dates) {
                setEndLaydate({min: dates});
            }
        }, o);
        util.setLaydate(options);
    }

    function setEndLaydate(o) {
        var options = $.extend({
            elem: '#enroll_end_time'
        }, o);
        util.setLaydate(options);
    }

    function delay (time, fn, context) {
        setTimeout(function(){
            fn.call(context || null);
        }, time)
    }

    setStartLaydate();
    setEndLaydate();
    
    /*this.El.find('.enrollType').on('click', 'label', function(e) {
        delay(100, function() {
            var curValue = _this.El.find('input[name="enroll_type"]:checked').val();
            if (curValue == 1 || curValue == 2) {
                data.enroll_type = curValue;
            }            
        })
    })*/

    var radioNoEnrollLimitWrap = $('#radioNoEnrollLimitWrap'),
        radioEnrollLimitWrap   = $('#radioEnrollLimitWrap');
    this.El.find('.auditing').on('click', 'label', function(e) {
        delay(100, function() {
            var curValue = _this.El.find('input[name="auditing"]:checked').val();
            if (curValue == 1) {
                _this.El.find('input[name="enroll_limit"][value=0]').siblings('label').click();
                radioEnrollLimitWrap.hide();
                radioNoEnrollLimitWrap.show();
                data.auditing = curValue;
            } else if (curValue == 0) {
                _this.El.find('input[name="enroll_limit"][value=1]').siblings('label').click();
                data.auditing = curValue;
                radioEnrollLimitWrap.show();
                radioNoEnrollLimitWrap.hide();
            }            
        })
    })

    this.El.find('.enrollLimit').on('click', 'label', function(e) {
        delay(100, function() {
            var curValue = _this.El.find('input[name="enroll_limit"]:checked').val();
            if (curValue == 1 || curValue == 0) {
                data.enroll_limit = curValue;
            }
            if (curValue != 1) {
                $('#enroll_limit_num_inp').val('').blur();
            }            
        })
    })

    this.El.find('.enrollFeeType').on('click', 'label', function(e) {
        delay(100, function() {
            var curValue = _this.El.find('input[name="enroll_fee_type"]:checked').val();
            if (curValue == 1 || curValue == 2 || curValue == 3) {
                data.enroll_fee_type = curValue;
            }
            if (curValue != 3) {
                $('#total_fee').val('').blur();
                data.enroll_fee = 0;
            }            
        });
    })

    this.El.find('#enroll_limit_num_inp').blur(function(e) {
        var target = $(e.target);
        data.enroll_limit = Number(target.val().trim());
    })

    this.El.find('#total_fee').blur(function(e) {
        var target = $(e.target);
        data.enroll_fee = Number(target.val().trim());
    })

    // set enroll_type
    this.El.find('input[name="enroll_type"][value='+ data.enroll_type +']').siblings('label').click();

    // set auditing
    this.El.find('input[name="auditing"][value='+ data.auditing +']').siblings('label').click();

    // set enroll_limit
    if (!data.enroll_limit) {
        this.El.find('input[name="enroll_limit"][value='+ data.enroll_limit +']').siblings('label').click();
    } else {
        this.El.find('input[name="enroll_limit"][value='+ 1 +']').siblings('label').click();
        this.El.find('#enroll_limit_num_inp').val(data.enroll_limit);
    }

    // set enroll_fee_type
    this.El.find('input[name="enroll_fee_type"][value='+ data.enroll_fee_type +']').siblings('label').click();

    function createEnrollAttr(value, defaultFlag) {
        var htmlEl = defaultFlag ? $('<a href="javascript:;" class="button button-m button-b-orange">'+ value +'</a>') :
                $('<a href="javascript:;" class="button button-m button-b-orange">'+ value +'<i class="icon iconfont"></i></a>');
        htmlEl.find('.iconfont').click(function(){
            htmlEl.remove();
            removeAttr(value);
        })
        if (!defaultFlag) {
            newEnrollAttrs.push(value);
        }
        $('#enrollAttr').append(htmlEl);
    }

    function isDefault(value) {
        return ~defaultAttrs.join('').indexOf(value);
    }

    function removeAttr(value) {
        for (var i = newEnrollAttrs.length - 1; i >= 0; i--) {
            if (newEnrollAttrs[i] == value) {
                newEnrollAttrs.splice(i, 1);
            }
        }
    }

    function checkUnique(value) {
        if (~$.inArray(value, defaultAttrs) || ~$.inArray(value, newEnrollAttrs)) {
            return false;
        }
        return true;
    }

    function iteratorAttrs(attrs) {
        $.each(attrs, function(i, enroll_attr) {
            if (isDefault(enroll_attr)) {
                createEnrollAttr(enroll_attr, true);
            } else {
                createEnrollAttr(enroll_attr);
            }
        })
    }

    function getAttr() {
        var result = [];
        $.each(defaultAttrs, function(index, attr) {
            result.push(attr);
        });
        $.each(newEnrollAttrs, function(index, attr) {
            result.push(attr);
        });
        return result;
    }

    if (data.enroll_attrs && data.enroll_attrs.length) {
        try {
            var enrollAttrs = data.enroll_attrs;
            iteratorAttrs(enrollAttrs);
        } catch (e) {

        }
    } else {
        iteratorAttrs(defaultAttrs);
    }

    var customFieldEl = this.El.find('#customField');
    this.El.find('#addCustomBtn').click(function() {
        var value = $.trim(customFieldEl.val());
        if (value) {
            if (!checkUnique(value)) {
                DialogUi.alert('该条件已被添加');
            } else {
                createEnrollAttr(value);
                customFieldEl.val('');                
            }
        } 
    })



    this.El.find('#registrationForm').validate({
        rules: {
            enroll_begin_time: 'isTime',
            enroll_end_time: 'isTime'
        },
        keyup: false,
        submitHandler: function() {
            var enroll_limit_num_inp = _this.El.find('#enroll_limit_num_inp'),
                total_fee = _this.El.find('#total_fee');
            var curData = _this.getData();

            if (new Date(curData.enroll_begin_time).getTime() > new Date(curData.enroll_end_time).getTime()) {
                return DialogUi.alert('报名开始时间要早于报名结束时间');
            }

            if (new Date(curData.enroll_begin_time).getTime() > new Date(curData.enroll_end_time).getTime()) {
                return DialogUi.alert('报名开始时间要早于报名结束时间');
            }

            if (_this.El.find('input[name="enroll_limit"]:checked').val() == 1) {
                if ($.type(data.enroll_limit) != 'number' || data.enroll_limit <= 0) {
                    return DialogUi.tip(enroll_limit_num_inp, '人数设置错误');
                }
            }

            if (data.enroll_fee_type == 3) {
                if ($.type(data.enroll_fee) != 'number' || data.enroll_fee <= 0) {
                    return DialogUi.tip(total_fee, '价格设置错误');
                } 
            } 

            _this.getInputData();
            var attrs = getAttr();
            _this.setData({enroll_attrs: JSON.stringify(attrs)});
            var datas = StepManager.getData();
            updateActivity(_this.getData(), function() {
                previewDialog(datas, function(next) {
                    next();
                    _this.next();
                })
            })
        }
    })

    this.El.find('#preStepBtn').click(function() {
        _this.back();
    })
})

// finish
var finishStep = StepManager.make('finishStep', null, function() {
    if (page.aid) {
        this.El.find('#manageCon').html('<a href="/community/activity/' + page.aid + 'manage/share" class="fs_fl_both fx_bg"></a>' +
                    '<a href="/community/activity/' + page.aid  + 'manage/sign/" class="fs_fl_both qd_bg"></a>' +
                    '<a href="/community/activity/' + page.aid + 'manage/check/" class="fs_fl_center sh_bg"></a>' +
                    '<a href="/community/activity/' + page.aid + 'manage/group/" class="fs_fl_both fz_bg"></a>' +
                    '<a href="/community/activity/' + page.aid + 'manage/inform/" class="fs_fl_both hd_bg"></a>' +
                    '<a href="/community/activity/' + page.aid + 'manage/photo/master/" class="fs_fl_center zp_bg"></a>');

        window._bd_share_config = {
            "common": {
                "bdSnsKey": {},
                "bdText": page.data.title,
                "bdMini": "2",
                "bdMiniList": false,
                "bdPic": page.data.cover_url,
                "bdStyle": "0",
                "bdSize": "32",
                "bdUrl" : getUrlHost()+"/wap/activity/detail?activity_id=" + page.aid,//活动分享地址填在这儿 
                "onAfterClick": function(cmd) {
                    //console.log(cmd);
                    if (cmd == 'weixin') {        
                      var timer = setInterval(function(){
                        if($('.bd_weixin_popup_head')){
                           $('.bd_weixin_popup_head > span').text('用微信扫一扫分享至朋友圈');
                           $('.bd_weixin_popup_foot').text('').parent().height(255);
                           clearInterval(timer);
                        }
                      },100) 
                    }
                }               
            },
            "share": {},
            "image": {
                "viewList": ["tsina", "weixin", "qzone", "renren", "sqq"],
                "viewText": "分享到：", "viewSize": "16"
            },
            "selectShare": {
                "bdContainerClass": null,
                "bdSelectMiniList": ["tsina", "weixin", "qzone", "renren", "sqq"]
            }
        };

        with (document)0[(getElementsByTagName('head')[0] || body).appendChild(createElement('script')).src = 'http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion=' + ~(-new Date() / 36e5)];
    }

    function getUrlHost() {
        var host = window.location.host,
            protocol = window.location.protocol;
        var origin = protocol+'//'+host;
        return origin;
    }
})

function previewActivity() {

}


page = {
    initialize: function() {
        $.validator.setDefaults({
            debug: true
        })
        page.status = 'create';
        page.aid = window.activityId || null;
        page._token = $('input[name="_token"]').val();
        if (page.aid) {
            page.status = 'update';
            var dialog = DialogUi.loading('正在加载中...');
            server.getActivity({activity: page.aid}, function(resp) {
                dialog.close();
                if (resp.code == 0) {
                    page.data = resp.activity;
                    StepManager.initData(page.data);
                    if (resp.activity.status == 0 && resp.activity.update_step == 5) {
                        StepManager.go(4);
                    } else {
                        StepManager.go(resp.activity.update_step || 1); 
                    }
                } else {
                    DialogUi.alert(resp.message || '获取活动数据失败,试试刷新重新获取');
                }
            })
        } else {
            page.data = {
                id: '',
                update_step: '',
                begin_time: '',
                end_time: '',
                contact: '',
                telephone: '',
                cover_url: '',
                images_url: [],
                address: '',/*'北京市海淀区西三环中路辅路;五棵松体育馆门口'*/
                brief_address: '',
                title: '',
                location: [],
                activity_line: [],/*[[30.611593, 104.124683], [30.616566, 104.103411], [30.61756, 104.053394], [30.602144, 104.080415]]*/
                detail: '',
                enroll_begin_time: '',
                enroll_end_time: '',
                enroll_type: '', // 1 anyone   2 team    3 secret
                enroll_limit: '', // numbers  0  >0   
                enroll_fee_type: '', // 1 mianfei  2  aa   3 shoufei
                enroll_fee: '',
                enroll_attrs: '',
                auditing: '' // 1 shenhe 0 bushenhe
            }
            StepManager.initData(page.data);
            StepManager.go(1);
        }
    }
}

page.initialize();

})
