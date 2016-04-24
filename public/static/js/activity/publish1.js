$(function() {

    var util = K.util;
    var TimePicker = K.TimePicker;
    var DialogUi = K.dialogUi;
    var Observe  = K.Observe;
    var server = K.server;

    var titleManager = (function() {
        var titleEl = $('#title'),
            title;

        function render() {
            titleEl.val(title);
        }

        return {
            set: function(title_value) {
                title = title_value || '';
                render();
            },
            get: function() {
                title = $.trim(titleEl.val());
                if (!title) {
                    DialogUi.tip(titleEl, '活动名称不能为空');
                    titleEl.focus();
                    return false;
                }
                return {title: title};
            }
        }
    })();

    var dateManager = (function() {
        var begin_time,
            end_time,
            enroll_begin_time,
            enroll_end_time;

        var curDate = new Date(),
            year = curDate.getFullYear(),
            month = curDate.getMonth() + 1,
            day = curDate.getDate(),
            curDate_format = year + '-' + (month < 10 ? '0' + month : month) + '-' + (day < 10 ? '0' + day : day);

        function formatTime(time) { // 01:00:00
            return time.slice(0, 5);
        }

        function fixTime(time) { // 00:00
            return time + ':00';
        }

        function getTime(time_array) {
            return new Date(getTimeFormat(time_array)).getTime();
        }

        function getTimeFormat(time_array) {
            return time_array[0] + ' ' + time_array[1];
        }

        function render() {
            // the start time of activity
            var act_start_date = $('#act_start_date').datepicker({
                numberOfMonths: 2,
                minDate: curDate,
                onSelect: function(date) {
                    begin_time[0] = date;
                    end_time[0] = date;
                    act_end_date.datepicker('option', 'minDate', new Date(K.StringToDate(date)));
                    enroll_start_date.datepicker('option', 'maxDate', new Date(K.StringToDate(date)));
                    enroll_end_date.datepicker('option', 'maxDate', new Date(K.StringToDate(date)));
                }
            }); 
            act_start_date.datepicker( "setDate", new Date(K.StringToDate(begin_time[0])));

            // the end time of activity
            var act_end_date = $('#act_end_date').datepicker({
                numberOfMonths: 2,
                minDate: curDate,
                onSelect: function(date) {
                    end_time[0] = date;
                }
            });
            act_end_date.datepicker( "setDate", new Date(K.StringToDate(end_time[0])));

            // the start time of enroll activity
            var enroll_start_date = $('#enroll_start_date').datepicker({
                numberOfMonths: 2,
                minDate: curDate,
                onSelect: function(date) {
                    enroll_begin_time[0] = date;
                    enroll_end_time[0] = date;
                    enroll_end_date.datepicker('option', 'minDate', new Date(K.StringToDate(date)));
                }
            });
            enroll_start_date.datepicker( "setDate", new Date(K.StringToDate(enroll_begin_time[0])));

            // the end time of enroll activity
            var enroll_end_date = $('#enroll_end_date').datepicker({
                numberOfMonths: 2,
                minDate: curDate,
                onSelect: function(date) {
                    enroll_end_time[0] = date;
                }
            });

            enroll_end_date.datepicker( "setDate", new Date(K.StringToDate(enroll_end_time[0])));

            var actStartPicker = new TimePicker({
                container: $('#actStartPick'),
                defaultValue: formatTime(begin_time[1]),
                select: function(value) {
                    begin_time[1] = fixTime(value);
                }
            });

            var actEndPicker = new TimePicker({
                container: $('#actEndPick'),
                defaultValue: formatTime(end_time[1]),
                select: function(value) {
                    end_time[1] = fixTime(value);
                }
            });

            var enrollStartPicker = new TimePicker({
                container: $('#enrollStartPick'),
                defaultValue: formatTime(enroll_begin_time[1]),
                select: function(value) {
                    enroll_begin_time[1] = fixTime(value);
                }
            });

            var enrollEndPicker = new TimePicker({
                container: $('#enrollEndPick'),
                defaultValue: formatTime(enroll_end_time[1]),
                select: function(value) {
                    enroll_end_time[1] = fixTime(value);
                }
            });

        }

        return {
            set: function(begin_time_value, end_time_value, enroll_start_time_value, enroll_end_time_value) {
                begin_time = begin_time_value ? begin_time_value.split(' ') : [curDate_format, '00:00:00'];
                end_time   = end_time_value ? end_time_value.split(' ') : [curDate_format, '00:00:00'];
                enroll_begin_time = enroll_start_time_value ? enroll_start_time_value.split(' ') : [curDate_format, '00:00:00'];
                enroll_end_time = enroll_end_time_value ? enroll_end_time_value.split(' ') : [curDate_format, '00:00:00'];
                render();
            },
            get: function() {
                if (getTime(begin_time) >= getTime(end_time)) {
                    DialogUi.tip($('#act_start_date'), '活动开始时间不能晚于活动结束时间');
                    $('body')[0].scrollTop = 0;
                    return false;
                }
                if (getTime(enroll_begin_time) >= getTime(enroll_end_time)) {
                    DialogUi.tip($('#enroll_start_date'), '报名开始时间不能晚于报名结束时间');
                    $('body')[0].scrollTop = 0;
                    return false;
                }
                if (getTime(enroll_end_time) >= getTime(begin_time)) {
                    DialogUi.tip($('#enroll_end_date'), '报名结束时间不能晚于活动开始时间');
                    $('body')[0].scrollTop = 0;
                    return false;
                }

                return {
                    begin_time: getTimeFormat(begin_time),
                    end_time: getTimeFormat(end_time),
                    enroll_begin_time: getTimeFormat(enroll_begin_time),
                    enroll_end_time: getTimeFormat(enroll_end_time)
                };
            }
        }
    })();



// address manager
    var addressManager = (function () {
        var addressMapCon = $('#addressMapCon'),
            addressInput  = $('#address'),
            addressTip    = $('#addressTip'),
            initMapFlag   = false,
            firstFlag     = true,
            location      = null,
            address       = null,
            brief_address = null;

        var map = (function() {
            var click_marker = null,
                addressGeoc  = null,
                addressMap   = null,
                addressLocal = null;

            function pos_getLocation(point, callback) {
                addressGeoc.getLocation(point, function (rs) {
                    var addComp = rs.addressComponents;
                    callback && callback(rs);
                });
            }

            function addOverlayToMap(point) {
                click_marker.setPosition(point);
                addressMap.addOverlay(click_marker);
            }

            return {
                init: function() {
                    click_marker = new BMap.Marker(),
                        addressGeoc  = new BMap.Geocoder(),
                        addressMap   = new BMap.Map("addressMap");

                    addressLocal = new BMap.LocalSearch(addressMap, {
                        renderOptions: {map: addressMap}
                        //pageCapacity: 8,
                       // onMarkersSet: function (pois) {console.log(pois);
                            //for (var i = 0; i < pois.length; i++) {
                               // pois[i].marker.addEventListener('click', function (e) {
                                //    selectPoint('', e.point, true);
                                //})
                            //}
                        //},
                        //onSearchComplete: function(results) {
                            //var datas = results.Vq;
                            //if (!datas.length) {
                            //    location = null;
                            //    brief_address = null;
                                //addressTip.html('未搜索到输入框内地址，你可以在地图中点击获取地址。');
                            //} else {
                                // var firstPoint = datas[0].point;
                                // location = [firstPoint.lat, firstPoint.lng];
                                // map.pos_getLocation(firstPoint, function(rs){
                                //     if (rs.address) {
                                //         var ac = rs.addressComponents;
                                //         setBriefAddress(ac);
                                //     }
                                // });
                                //addressTip.html('搜索到' + datas.length +　'个相关地址，通过地图点击获取地址。');
                            //}
                            //SearchPanel.render(results.Vq);
                        //}
                    });

                    addressMap.addEventListener("click", function (e) {
                        //if (!e.overlay) {
                        //addOverlayToMap(e.point);
                        //pos_getLocation(e.point);
                        selectPoint('', e.point, true);
                        e.domEvent.stopPropagation();
                        //}
                    });

                    addressMap.enableScrollWheelZoom();
                },
                setPoint: function(flag, defaultName) {
                    var point, rate = 12, showPoint = false;
                    if (location && location.length > 0) {
                        point = new BMap.Point(location[1], location[0]);
                        rate = 15;
                    } else {
                        rate = 12;
                        // point = new BMap.Point(104.073516, 30.653854); // 默认中心点指向北京中心
                        showPoint = true;
                    }

                    if (!flag || (page.aid && firstFlag)) {
                        firstFlag = false;
                        addressMap.centerAndZoom(point || defaultName, rate);
                    }

                    if (!showPoint) {
                        addOverlayToMap(point);
                    }
                },
                searchPoint: function(value, callback) {callback = callback;
                    if (!value) return false;
                    var that = this;
                    addressLocal.setSearchCompleteCallback(function(rs){
                        if (  addressLocal.getStatus() == BMAP_STATUS_SUCCESS ) return;
                        var myGeo = new BMap.Geocoder();// 将地址解析结果显示在地图上,并调整地图视野
                        myGeo.getPoint(rs.keyword, function(point){
                            console.log(point);
                            if ( point ) {
                                addOverlayToMap( point );
                                that.setCenterAndZoom(point,15);
                            }else {
                                DialogUi.alert('无法解析该地址，请确认后重新输入。');
                                return;
                            }
                        });
                    });
                    addressLocal.search(value);
                },
                clearPoint: function() {
                    addressMap.clearOverlays();
                },
                setCenterAndZoom : function(point,zoom) {
                    addressMap.centerAndZoom(point,zoom);
                },
                pos_getLocation: pos_getLocation
            }

        })();

        function setSearchLocalCity(address) {
            return cityName + ' ' + address;
        }

        //var focusFlag = false;
        //addressInput.focus(function() {
            //addressMapCon.show();
            // if (!initMapFlag) {
            //     map.init();
            //     initMapFlag = true;
            // }
            // map.setPoint();
            // focusFlag = true;
        //});

        addressInput.blur(function() {
            //addressMapCon.hide();
            //addressTip.html('');
            var value = this.value;
            if (value) {
                address = value;
            }
            //focusFlag = false;
        });

        var firstFlag = true;
        $('#setNavigation').click(function() {
            var display = addressMapCon.css('display');
            if (display == 'block') {
                addressMapCon.hide();
                $('.add-alias-tip').hide();
                $(this).text('设置导航');
            } else if (display == 'none') {
                addressMapCon.show();
                $('.add-alias-tip').show();
                $(this).text('收起导航');
                setTimeout(function() {
                    if (!initMapFlag) {
                        map.init();
                        initMapFlag = true;
                    }

                    if (location && location.length) {
                        // 已经设置了导航点
                        map.setPoint(true);
                    } else if (address) {
                        // 设置了地址
                        map.searchPoint(setSearchLocalCity(address));
                    } else {
                        map.setPoint(false, cityName);
                    }                    
                }, 200);

            }
        });

        function setBriefAddress(ac) {
            if (!ac.street && !ac.streetNumber) {
                brief_address = ac.city + ac.district;
            } else {
                brief_address = ac.street + ac.streetNumber;
            }
        }

        function selectPoint(address_value, point, flag) {
            // if (address_value) {
            //     address = address_value;
            //     addressInput.val(address);
            //     !flag && addressInput.focus();
            // }
            map.clearPoint();
            location = [point.lat, point.lng];
            map.setPoint(flag);
            //addressTip.html('');
            //SearchPanel.clear();
            map.pos_getLocation(point, function(rs){
                if (rs.address) {
                    //address = rs.address;
                    //addressInput.val(address);
                    //!flag && addressInput.focus();
                    var ac = rs.addressComponents;
                    setBriefAddress(ac);
                }
            });

            DialogUi.message('已设置导航');
        }

        /* var SearchPanel = (function() {
         var el = $('<div class="address-search-panel"></div>');
         addressInput.after(el);
         el.hide();

         function clear() {
         el.html('');
         el.hide();
         }

         return {
         render: function(datas) {
         var nav = $('<ul class="address-search-nav"></ul>');
         if (datas.length) {
         $.each(datas, function(index, data) {
         var li = $('<li><a href="javascript:;">' + data.address + '</a></li>');
         li.click(function(e){
         selectPoint(data.address, data.marker.z.point);
         })
         nav.append(li);
         })
         el.html(nav);
         el.show();
         } else {
         clear();
         }
         },
         clear: function() {
         clear();
         }
         }
         })()*/

        /*var searchInMap = util.throttle(function(value, callback){
            map.searchPoint(value);
        }, 600);*/

        //var value = '';
        //addressInput.keyup(function() {
            // var new_value = $(this).val();
            // if (new_value != value) {
            //     value = new_value;
            //     searchInMap(new_value);
            // }
        //});

        util.outsiteClick(addressMapCon, function() {
            addressMapCon.hide();
        }, [
            { className: 'act-address-input' },
            { className: 'set-nav-btn' },
            { parent: 'address-map' }
        ]);

        return {
            set: function(address_value, brief_address_value, location_value) {
                location = location_value || [];
                address = address_value || '';
                brief_address = brief_address_value || '';
                if (location.length > 1) {
                    location[0] = Number(location[0]);
                    location[1] = Number(location[1]);                    
                }
                addressInput.val(address || '');
            },
            get: function() {
                address = $.trim(addressInput.val());
                if (!address) {
                    DialogUi.tip(addressInput, '活动地址不能为空');
                    addressInput.focus();
                    return false;
                }
                if (!location || !location.length) {
                    DialogUi.message('还没设置导航');
                    $('body')[0].scrollTop = 0;
                    return false;
                }

                brief_address = address;
                return {
                    address: address,
                    brief_address: brief_address,
                    location: JSON.stringify(location)
                }
            }
        }
    })();

// enroll_fee_type
    var enrollFeeManager = (function() {
        var enrollFeeType = $('#enrollFeeType'),
            enrollFeeTip = $('#enrollFeeTip'),
            total_fee_input = $('#total_fee'),
            enroll_fee_type = 1, // default
            enroll_fee = '';

        lc.SelectManager.on(enrollFeeType, function(value) {
            enroll_fee_type = value;
            setContentByEnrollType.render();
        })

        var setContentByEnrollType = {
            enrollFeeFree: $('#enrollFeeFree'),
            enrollFeeAA: $('#enrollFeeAA'),
            enrollFeeUnfree: $('#enrollFeeUnfree'),
            render: function() {
                enrollFeeTip.show();
                if (enroll_fee_type == 1) {
                    this.enrollFeeFree.show();
                    this.enrollFeeAA.hide();
                    this.enrollFeeUnfree.hide();
                } else if (enroll_fee_type == 2) {
                    this.enrollFeeFree.hide();
                    this.enrollFeeAA.show();
                    this.enrollFeeUnfree.hide();
                } else if (enroll_fee_type == 3) {
                    this.enrollFeeFree.hide();
                    this.enrollFeeAA.hide();
                    this.enrollFeeUnfree.show();
                    total_fee_input.val(enroll_fee).focus();
                }
            }
        }

        total_fee_input.focus(function() {
            var value = Number(this.value);
            if (isNaN(value) || value <= 0) {
                this.value = '';
            }
        });

        total_fee_input.blur(function() {
            var value = Number(this.value);
            if (value && value > 0) {
                enroll_fee = value;
            } else {
                this.value = 0;
            }
        });

        return {
            set: function(enroll_fee_type_value, enroll_fee_value) {
                enroll_fee = enroll_fee_value || '';
                enroll_fee_type = enroll_fee_type_value || 1;
                $('input[name="enroll_fee_type"][value='+enroll_fee_type+']').siblings('label').trigger('click');
                setContentByEnrollType.render();
            },
            get: function() {
                if (enroll_fee_type == 3 && !enroll_fee) {
                    DialogUi.tip(total_fee_input, '请填写活动收取费用');
                    //total_fee_input.focus();
                    return false;
                }
                if (enroll_fee_type == 1 || enroll_fee_type == 2) {
                    enroll_fee = 0;
                }
                return {
                    enroll_fee_type: enroll_fee_type,
                    enroll_fee: enroll_fee
                }
            }
        }
    })();

// auditing
    var auditingManager = (function() {
        var auditingEl = $('#auditing'),
            auditingTip = $('#auditingTip'),
            enroll_limit_input = $('#enroll_limit_num_inp'),
            auditing = 0, // default
            enroll_limit = 0;

        lc.SelectManager.on(auditingEl, function(value) {
            auditing = value;
            setContentByAuditing.render();
        })

        var setContentByAuditing = {
            aduitingNeed: $('#aduitingNeed'),
            aduitingUnNeed: $('#aduitingUnNeed'),
            render: function() {
                auditingTip.show();
                if (auditing == 0) {
                    this.aduitingNeed.show();
                    this.aduitingUnNeed.hide();
                    enroll_limit_input.val(enroll_limit);
                } else if (auditing == 1) {
                    this.aduitingNeed.hide();
                    this.aduitingUnNeed.show();
                }
            }
        }

        enroll_limit_input.focus(function() {
            var value = Number(this.value);
            if (isNaN(value) || value <= 0) {
                this.value = '';
            }
        });

        enroll_limit_input.blur(function() {
            var value = Number(this.value);
            if (status == 'publish') {
                if (value < enroll_limit) {
                    DialogUi.tip(enroll_limit_input, '发布状态中，报名人数不能少于初次设定的'+enroll_limit+'人次');
                    enroll_limit_input.val(enroll_limit);
                    return false;
                }
            }

            if (value && value > 0) {
                enroll_limit = value;
            } else {
                this.value = 0;
            }
        });

        return {
            set: function(auditing_value, enroll_limit_value) {
                enroll_limit = enroll_limit_value || 0;
                auditing = auditing_value || 0;
                $('input[name="auditing"][value='+auditing+']').siblings('label').trigger('click');
                setContentByAuditing.render();
            },
            get: function() {
                if (!auditing && !enroll_limit) {
                    DialogUi.tip(enroll_limit_input, '请设置报名人数');
                    //enroll_limit_input.focus();
                    return false;
                }
                if (auditing == 1) {
                    enroll_limit = 0;
                }
                return {
                    auditing: auditing,
                    enroll_limit: enroll_limit,
                    enroll_type: 1
                }
            }
        }
    })();

    var enrollAttrsManager = (function() {
        var enroll_attrs = [],
            defaultAttrs = ['手机号', '姓名'],
            enrollAttrs = $('#enrollAttrs'),
            preveiwMobile = $('#preveiwMobile');

        var throttle = util.throttle;

        function createEnrollAttr(value, defaultFlag) {
            var htmlEl = defaultFlag ?
                $('<div class="ui-infor" data-id="1">' +
                    '<a href="javascript:;" class="ui-infor-link">' + value + '</a>' +
                    '</div>') :
                $('<div class="ui-attr-item"><input type="text" value="'+ value +'" class="form-control" placeholder="请输入报名条件" />' +
                    '<a href="javascript:;" class="ui-attr-item-del"><i class="icon iconfont"></i></a></div>');

            htmlEl.find('.iconfont').click(function() {
                htmlEl.remove();
                getAttr();
            });

            htmlEl.find('input').keyup(function() {
                throttleKeyUpHandler(this.value, $(this));
            });

            enrollAttrs.append(htmlEl);
        }

        function checkUnique(value) {
            var obj = {};
            $.each(enroll_attrs, function(index,attr) {
                if (!obj[attr]) {
                    obj[attr] = 1;
                } else {
                    obj[attr] ++;
                }
            });

            if (obj[value] > 1) {
                return false;
            }
            return true;
        }

        var throttleKeyUpHandler = throttle(function(value, el){
            getAttr();
            if (!checkUnique(value)) {
                DialogUi.tip(el, '该条件已存在');
            }
        }, 500);

        function getAttr() {
            var result = [];
            $.each(defaultAttrs, function(index, attr) {
                result.push(attr);
            });

            var inputs = enrollAttrs.find('.form-control');
            $.each(inputs, function(i){
                var value = inputs.eq(i).val();
                if (value) {
                    result.push(value);
                }
            });
            enroll_attrs = result;
            previewInMobile();
        }

        function isDefault(value) {
            return ~defaultAttrs.join(',').indexOf(value);
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

        function renderEnrollAttrs() {
            if (enroll_attrs && enroll_attrs.length) {
                iteratorAttrs(enroll_attrs);
            } else {
                iteratorAttrs(defaultAttrs);
            }
            getAttr();
        }

        $('#addAttrsBtn').click(function(){
            createEnrollAttr('');
        })

        function previewInMobile() {
            var attrs = enroll_attrs.length ? enroll_attrs : defaultAttrs;
            var result0 = '<h3 class="title">报名条件预览:</h3>';
            var result = '';
            $.each(attrs, function(index, attr) {
                result += '<input type="text" value="'+ attr +'" class="attr-input" readOnly="true" />';
            });
            result = '<div class="preview-mobileAttr-w">' + result + '</div>';
            preveiwMobile.html(result0 + result);
        }

        return {
            set: function(enroll_attrs_array) {
                if (enroll_attrs_array) {
                    if ($.type(enroll_attrs_array) == 'array' && enroll_attrs_array.length) {
                        enroll_attrs = enroll_attrs_array;
                    }
                }
                renderEnrollAttrs();
            },
            get: function() {
                var attrInputs = $('#enrollAttrs').find('.form-control');
                for (var i = defaultAttrs.length ; i < enroll_attrs.length; i++) {
                    if (!checkUnique(enroll_attrs[i])) {
                        var curAttr = attrInputs.eq(i - defaultAttrs.length);
                        DialogUi.tip(curAttr, '该条件已存在');
                        curAttr.focus();
                        return false;
                    }
                }
                return {
                    enroll_attrs: JSON.stringify(enroll_attrs)
                }
            }
        }
    })();

    var contactManager = (function() {
        var contactEl = $('#contact'),
            telephoneEl = $('#telephone'),
            contact,
            telephone;

        function renderContact() {
            contactEl.val(contact);
            telephoneEl.val(telephone);
        }

        function getData() {
            contact = contactEl.val();
            telephone = telephoneEl.val();
        }

        return {
            set: function(contact_value, telephone_value) {
               // var default_contact = contactEl.val(),
                 //   default_telephone = telephoneEl.val();
                //if (default_contact) {
                //    contact = default_contact;
                //} else {
                    contact = contact_value || '';
                //}
                //if (default_telephone) {
                //    telephone = default_telephone
               // } else {
                    telephone = telephone_value || '';
               //}

                renderContact();
            },
            get: function() {
                getData();
                if (!contact) {
                    DialogUi.tip(contactEl, '联系人不能为空');
                    contactEl.focus();
                    return false;
                }
                /*if (!telephone) {
                    DialogUi.tip(telephoneEl, '联系电话不能为空');
                    return false;
                }*/
                if (telephone && !K.isPhone(telephone) && !K.isTel(telephone)) {
                    DialogUi.tip(telephoneEl, '联系电话格式错误');
                    telephoneEl.focus();
                    return false;
                }
                return {
                    contact: contact,
                    telephone: telephone
                }
            }
        }
    })();

    var ActiveImg = function(options) {
        this.data = options;
        this.initialize();
    }

    ActiveImg.prototype = {
        constructor: ActiveImg,
        initialize: function() {
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

    var imagesUrlManager =(function() {
        var images_url = '',
            cover_url  = null,
            uploader,
            activeImgs = [];
        var uploadSelector = $('#uploadSelector');

        // 初始化图片上传 webuploader
        function setImageUploader() {
            uploader = WebUploader.create({
                formData: {_token: page._token},
                fileVal: 'image',
                auto: true,
                swf: '/static/plugins/webuploader/Uploader.swf',
                server: '/community/image/tmp/upload',
                pick: '.ui-uploadThumb-icon',
                accept: {
                    title: 'Images',
                    extensions: 'jpg,png',
                    mimeTypes: 'image/*'
                }
            });

            uploader.on('uploadSuccess', function(file, resp) {
                if (resp.code == 0) {
                    var curData = {url: resp.image_url};
                    createActiveImg(curData);
                } else {
                    DialogUi.alert(resp.message || '上传失败');
                }
            });

            uploader.on('error', function(e) {
                if ( e == 'Q_TYPE_DENIED' ) {
                    DialogUi.alert('对不起，暂不支持上传该种类型的图片，请选择其它图片上传，目前仅支持.jpg ，.png后缀类型的图片。');
                }
            });

            uploader.on('uploadError', function(file) {
                DialogUi.alert('上传失败');
            });
        }

        function setImagesUriToData() {
            var result = [];
            $.each(activeImgs, function(i, activeImg) {
                result.push(activeImg.data.url);
            })
            images_url = JSON.stringify(result);
        }


        function createActiveImg(imgData) {
            if (activeImgs.length >= 4) {
                DialogUi.alert('至多上传4张活动照片');
                return;
            }

            var activeImg = new ActiveImg(imgData);
            uploadSelector.before(activeImg.El);

            activeImg.on('setCover', function(ai) {
                cover_url = ai.data.url;
                for (var i = 0; i < activeImgs.length; i++) {
                    if (activeImg.data.url != activeImgs[i].data.url) {
                        activeImgs[i].data.cover = false;
                        activeImgs[i].render();
                    } else if (i > 0) {
                        activeImgs[0].El.before(activeImgs[i].El);
                        var a = activeImgs[i];
                        activeImgs.splice(i, 1);
                        activeImgs.splice(0, 0, a);
                    }
                }
            })

            activeImg.on('cancelCover', function(ai) {
                cover_url = '';
            })

            activeImg.on('close', function(ai) {
                if (cover_url == ai.data.url) {
                    cover_url = '';
                }
                for (var i = activeImgs.length - 1; i >=0; i--) {
                    if (activeImgs[i].data.url == ai.data.url) {
                        activeImgs.splice(i, 1);
                    }
                }
                if (activeImgs.length < 4) {
                    uploadSelector.show();
                }
                uploader.reset();
            })

            activeImgs.push(activeImg);
            if (activeImgs.length >= 4) {
                uploadSelector.hide();
            }
        }

        function render(images) {
            if (images.length >= 4) {
                uploadSelector.hide();
            }
            $.each(images, function(i, img_url) {
                var img = {};
                img.url = img_url;
                if (cover_url == img_url) {
                    img.cover = true;
                }
                createActiveImg(img);
            });
        }

        return {
            set: function(images_url_array, cover_url_value) {
                cover_url = cover_url_value || '';
                if (images_url_array.length) {
                    if ($.type(images_url_array) == 'array') {
                        images_url = images_url_array;
                    }
                    render(images_url_array);
                }
                setImageUploader();
            },
            get: function() {
                if (!activeImgs.length) {
                    DialogUi.message('至少需要上传一张图片');
                    return false;
                }
                if (!cover_url) {
                    // 没有手动设置封面，自动设置第一张为封面
                    cover_url = activeImgs[0].data.url;
                }
                setImagesUriToData();
                return {
                    images_url: images_url,
                    cover_url: cover_url
                }
            }
        }
    })();

    var detailManager = (function() {
        var errorTip = $('.detail-error-tip'),
            detail = '';

        ue = UE.getEditor('container', {
            toolbars: [
                ['undo', 'redo', 'bold', 'italic', 'underline', 'fontborder', 'fontfamily', 'fontsize', 'strikethrough', 'removeformat', '|', 'forecolor', 'backcolor', 'cleardoc', 'preview']
            ],
            elementPathEnabled: false,
            maximumWords: 5000,
            initialFrameHeight: 200
        });

        return {
            set: function(detail_text) {
                detail = detail_text || '';
                if (detail) { // 设置默认值
                    ue.ready(function() {
                        ue.setContent(detail);
                    })
                }
            },
            get: function() {
                var contentLength = ue.getContentLength(true);
                if (contentLength > 5000) {
                    DialogUi.tip(errorTip, '亲，活动详情内容不得超过5000字！');
                    return false;
                }

                detail = ue.getContent();
                errorTip.html('')
                return {
                    detail: detail
                };
            }
        }
    })();

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

    var page = {
        initialize: function() {
            this.managers = [];
            page.aid = Number(window.activityId || null);
            page._token = $('input[name="_token"]').val();
            if (page.aid) {
                var dialog = DialogUi.loading('正在加载中...');
                server.getActivity({activity: page.aid}, function(resp) {
                    dialog.close();
                    if (resp.code == 0) {
                        page.data = resp.activity;
                        page.setValue(page.data);
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
                this.setValue(page.data);
            }
            this.setEvents();
        },
        setValue: function() {
            var data = page.data;
            titleManager.set(data.title);
            dateManager.set(data.begin_time, data.end_time, data.enroll_begin_time, data.enroll_end_time);
            addressManager.set(data.address, data.brief_address, data.location);
            enrollFeeManager.set(data.enroll_fee_type, data.enroll_fee);
            auditingManager.set(data.auditing, data.enroll_limit);
            enrollAttrsManager.set(data.enroll_attrs);
            contactManager.set(data.contact, data.telephone);
            imagesUrlManager.set(data.images_url, data.cover_url);
            detailManager.set(data.detail);
            this.managers.push(titleManager, dateManager, addressManager, enrollFeeManager, auditingManager,
                enrollAttrsManager, contactManager, imagesUrlManager, detailManager);
        },
        getValue: function() {
            var result = [],
                result_data = {},
                title_data,
                managers = this.managers;
            for (var i = 0; i < managers.length; i++) {
                var manager_data = managers[i].get();
                if (manager_data) {
                    result.push(manager_data);
                } else {
                    return false;
                }
            }

            for (var j = 0; j < result.length; j++) {
                if ($.type(result[j]) == 'object') {
                    result_data = $.extend(result_data, result[j]);
                }
            }

            return result_data;
        },
        saveActivity: function(datas, callback) {
            if (!page.aid) {
                createActivity(datas, function(activity) {
                    page.aid = activity;
                    callback && callback();
                })
            } else {
                updateActivity(datas, function() {
                    callback && callback();
                })
            }
        },
        setEvents: function() {
            var _this = this;
            $('#saveBtn').click(function(e) {
                var datas = _this.getValue();
                if (!datas) return false;
                var dialog = DialogUi.loading('活动保存中...');
                _this.saveActivity(datas, function() {
                    DialogUi.message('活动保存成功');
                    location.href='/community/activity/list';
                    dialog.close();
                });
            });


            $('#publishBtn').click((function(e) {
                var flag = false;
                return function() {
                    if (flag) return;
                    flag = true;
                    var datas = _this.getValue();
                    if (!datas) {
                        flag = false;
                        return false;
                    }
                    var dialog = DialogUi.loading('活动发布中...');
                    datas.publish = 1;
                    _this.saveActivity(datas, function() {
                        flag = false;
                        DialogUi.message('活动发布成功');
                        location.href='/community/activity/list';
                        dialog.close();
                    });
                }
            })());
        }
    }

    page.initialize();

});