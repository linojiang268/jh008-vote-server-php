(function(){

    var Class = K.Class,
        BaseView = K.util.BaseView,
        DialogUi = K.dialogUi,
        Observe = K.Observe;

    var photos = K.ns('photos');

    // OperateBar 按钮组配置项
    var OperateBarConfig = {
        uploadBtn: {
            element: function() {
                return '<a href="javascript:;" class="upload">上传照片<i class="icon iconfont"></i></a>';
            },
            type: 'normal'
        },
        batchBtn: {
            element: function() {
                return '<a href="javascript:;" class="button button-orange batch">批量操作</a>';
            },
            type: 'normal'
        },
        passBtn: {
            element: function() {
                return '<a href="javascript:;" class="button button-blue pass">通过审核</a>';
            },
            type: 'batch'
        },
        tipBtn: { 
            element: function(nums) {
                return  '<span class="tip">你选择了<span class="nums">' + nums + '</span>张</span>';
            },
            type: 'batch'
        },
        deleteBtn: {
            element: function(nums) {
                return nums > 0 ? '<a href="javascript:;" class="button button-orange delete">删除</a>' :
                                  '<a href="javascript:;" class="button button-orange button-disabled delete">删除</a>';
            },
            type: 'batch'
        },
        finishBtn: {
            element: function() {
                return '<a href="javascript:;" class="button button-blue finish fr">完成操作</a>';
            },
            type: 'batch'
        }
    }

    function tip(message) {
        DialogUi.alert(message);
    }

    var loadDia;
    function setLogoUploader(El, _token, activity, context) {
        var uploader = WebUploader.create({
            formData: {_token: _token, activity: activity},
            fileVal: 'image',
            auto: true,
            swf: '/static/plugins/webuploader/Uploader.swf',
            server: '/community/activity/album/image/add',
            pick: El,
            accept: {
                title: 'Images',
                extensions: 'gif,jpg,jpeg,bmp,png',
                mimeTypes: 'image/*'
            }
        })

        uploader.on('uploadSuccess', function(file, resp) {
            if (resp.code == 10000) {
              tip(resp.messages || '上传照片出错了!');
            } else if (resp.code == 0) {
                context.trigger('upload', {
                    image_url: resp.image_url,
                    id: resp.id
                });
            }
            loadDia && loadDia.close();
        })

        uploader.on('uploadError', function(file) {
            tip('上传照片出错了!');
            loadDia && loadDia.close();
         //context.trigger('upload', {id: Math.floor(Math.random()*1000), image_url: "http://dev.image.jhla.com.cn/default/activity1.png"})
        }) 

        uploader.on('startUpload', function(file, resp){
           loadDia = DialogUi.loading('照片上传中...');
        })
    }

    /**
     * OperateBar 相册控制条
     * @parms {Object} 
            - btns {Array} ['batchBtn', 'passBtn', ...] 如上OperateBarConfig配置项.
            - nums {Number} 默认数据nums
     */
    photos.OperateBar = Class.create(BaseView, {
        initialize: function(options) {
            var defaultOptions = {
                btns: ['uploadBtn', 'batchBtn', 'passBtn', 'tipBtn', 'deleteBtn', 'finishBtn']
            };
            this.El = $('<div class="operate-bar"></div>');
            this.status = 'normal'; // normal | batch
            this.nums = (options && options.nums) || 0;
            if (options && options.nums) delete options.nums;
            this.options = $.extend(defaultOptions, options);
            this.render();
            this.setEvents();
        },
        events: {
            'click .batch': 'batchHandler',
            'click .pass': 'passHandler',
            'click .delete': 'deleteHandler',
            'click .finish': 'finishHandler'
        },
        eventsHandler: {
            batchHandler: function() {
                this.setNums(0);
                this.setStatus('batch');
                this.trigger('batch');
            },
            passHandler: function() {
                this.trigger('pass');
            },
            deleteHandler: function() {
                this.trigger('delete');
            },
            finishHandler: function() {
                this.setNums(0);
                this.setStatus('normal');
                this.trigger('finish');
            }
        },
        render: function() {
            var options = this.options, 
                _this = this,
                status = _this.status,
                innerBtnsHtmlString = '',
                innerHtmlString = '';
            $.each(options.btns, function(i, btn) {
                var btnObject = OperateBarConfig[btn];
                if (btnObject) {
                    var type = btnObject.type,
                        element = btnObject.element;
                    if (type == status) {
                        innerBtnsHtmlString += element(_this.nums);
                    }                    
                }
            })
            innerHtmlString = '<div class="operate-wrap '+ (status == 'batch' ? 'batch-status' : '') +'">' + innerBtnsHtmlString + '</div>';
            this.El.html(innerHtmlString);
            if (this.El.find('.upload') && this.hasBtn('uploadBtn')) {
                setLogoUploader(this.El.find('.upload'), this.options._token, this.options.activity, this);
            }
        },
        setStatus: function(status) {
            this.status = status;
            this.render();
        },
        setNums: function(length) {
            this.nums = length;
            this.render();
        },
        hasBtn: function(btnName) {
            if (~$.inArray(btnName, this.options.btns)) {
                return true;
            } else {
                return false;
            }
        }
    });

    Observe.make(photos.OperateBar.prototype);

    /**
     * PhotosManager 相册管理器 
     * @parms {Object}
            - datas  初始化相册数据
     */
    photos.PhotosManager = Class.create(BaseView, {
        initialize: function(options) {
            var defaultOptions = {};
            this.options = $.extend(defaultOptions, options);
            this.datas = options.datas || [];
            this.photos = [];
            this.selects = [];
            this.El = $('<ul class="photos clearfix"></div>');
            this.status = 'normal';
            this.render();
        },
        render: function() {
            var photos = this.photos,
                datas = this.datas,
                photosLength = photos.length,
                datasLength = datas.length;
            // 优化.
            if (photosLength < datasLength) {
                for (var i = photosLength; i < datasLength; i++) {
                    var photo = this.createPhoto(datas[i]);
                    this.El.append(photo.El);
                    this.photos.push(photo);                      
                }
            }
        },
        createPhoto: function(data) {
            var _this = this;
            var photo = new photos.Photo({data: data});
            photo.on('select', function(photo) {
                _this.addSelect(photo);
            });
            photo.on('unselect', function(photo) {
                _this.removeSelect(photo);
            })
            photo.on('click', function() {
                _this.trigger('click', _this.datas);
            })
            return photo;
        },
        setStatus: function(status) {
            this.status = status;
            $.each(this.photos, function(i, photo) {
                photo.setStatus(status);
            })
        },
        getSelectsData: function() {
            var datas = [];
            $.each(this.selects, function(i, selectPhoto) {
                datas.push(selectPhoto.data);
            })
            return datas;
        },
        getLastData: function() {
            return this.datas[this.datas.length-1];
        },
        addSelect: function(photo) {
            var flag = false, selects = this.selects;
            for (var i = 0; i < selects.length; i++) {
                if (selects[i] == photo) {
                    flag = true;
                    break;
                }
            }
            if (!flag) {
                selects.push(photo);
                this.trigger('select', this.getSelectsData());
            }
        },
        removeSelect: function(photo) {
            var selects = this.selects;
            for (var i = 0; i < selects.length; i++) {
                if (selects[i] == photo) {
                    selects.splice(i, 1);
                    this.trigger('select', this.getSelectsData());
                }
            }
        },
        _appendItem: function(data, index) {
            if (this.status == 'batch') return;
            this.datas.splice(index, 0, data);
            var photo = this.createPhoto(data);
            if (index < 0) {
                this.El.prepend(photo.El);
                this.photos.unshift(photo);
            } else if (index >= this.photos.length) {
                this.El.append(photo.El);
                this.photos.push(photo);
            } else {
                this.photos[index].El.after(photo.El);
                this.photos.splice(index, 0, photo);
            }
        },
        /**
         * @params {Array}  datas
         * @params {Number} 1 正序   0 倒序
         * @params {Index}  index 插入数据的序列
         */
        _append: function(datas, sequence, index) {
            var index = $.type(index) == 'number' ? index : this.photos.length;
            if ($.type(datas) != 'array') return;
            for (var i = 0; i < datas.length; i++) {
                var data = datas[i];
                if (sequence == 0) { // 倒序
                    this._appendItem(data, index);
                } else {
                    this._appendItem(data, index + i);
                }
            }
        },
        getAppendDataFormat: function(data) {
            var result = [];
            if ($.type(data) != 'array') {
                result.push(data);
            } else {
                result = data;
            }
            return result;
        },
        appendBefore: function(data) { // 插入一条照片
            this._append(this.getAppendDataFormat(data), 0, -1);
        },
        append: function(data) { // 正序插入
            this._append(this.getAppendDataFormat(data), 1);
        },
        doDelete: function() {
            var selects = this.selects,
                photos = this.photos,
                datas = this.datas;
            for(var i = selects.length - 1; i >= 0; i--) {
                for (var j = photos.length - 1; j >= 0; j--) {
                    if (photos[j] == selects[i]) {
                        photos[j].destroy();
                        photos.splice(j ,1);
                        datas.splice(j, 1);
                        selects.splice(i, 1);
                        break;
                    }
                }
            }
            this.trigger('select', this.getSelectsData());
        }
        // insert
    });

    Observe.make(photos.PhotosManager.prototype);


    /**
     * @Photo 相片
     * @parms {Object} 
            - data 初始化照片的数据
     */
    photos.Photo = Class.create(BaseView, {
        initialize: function(options) {
            var defaultOptions = {};
            this.options = $.extend(defaultOptions, options);
            this.data = options.data || [];
            this.El = $('<li class="photo"></li>');
            this.status = 'normal'; // batch
            this.select = false;
            this.render();
            this.setEvents();
        },
        events: {
            'click': 'singleClick'
        },
        eventsHandler: {
            singleClick: function(e) {
                if (this.status == 'normal') {
                    this.trigger('click');
                } else if (this.status == 'batch') {
                    this.setSelect(!this.select);
                }
                e.stopPropagation();
            }
        },
        getElement: function() {
            var selflagClass = this.select == true ? 'select' : '';
            return  '<div class="photo-wrap">' +
                        '<a href="javascript:;" class="photo-link">' +
                            '<img src="'+ this.data.image_url +'" alt="">' +
                        '</a>' +
                        (this.status == 'batch' ? '<a href="javascript:;" class="selflag '+ selflagClass +'"><i class="icon iconfont"></i></a>' : '') +
                    '</div>';
        },
        render: function() {
            if (this.status == 'batch') {
                this.El[0].className = 'photo batch-photo';
            } else if (this.status == 'normal') {
                this.El[0].className = 'photo';
            }
            this.El.html(this.getElement());
        },
        setStatus: function(status) {
            this.status = status;
            if (status == 'normal') {
                this.setSelect(false);
            } else {
                this.render();
            }
        },
        setSelect: function(flag) {
            this.select = flag;
            this.render();
            if (flag) {
                this.trigger('select', this);
            } else {
                this.trigger('unselect', this);
            }
        },
        destroy: function() {
            this.El.remove();
            this.undelegate();
        }
    })

    Observe.make(photos.Photo.prototype);


})()