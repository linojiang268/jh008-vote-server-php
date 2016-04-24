/**
 * 基于photo.js, 创造偏向当前相册业务的模块
 * @params el jquery el
 * @params 
        - btns
        - _token
        - type
        - scroll 是否检测滚动到底部事件
 */
(function() {

var DialogUi = K.dialogUi,
    Observe  = K.Observe,
    server   = K.server,
    photos   = K.photos,
    util     = K.util;

var syncPhoto = {
    remove: server.removeAlbum,
    list: server.listAlbum,
    approve: server.approveAlbum
}

function tip(message) {
    DialogUi.message(message);
}

function formateDate(date) {
    var date  = date || new Date();
    var year  = date.getFullYear();
    var month = date.getMonth() + 1;
    var day   = date.getDate();
    var hour  = date.getHours();
    var minute= date.getMinutes();
    var second= date.getSeconds();
    return year + "-" + month + '-' + day + ' ' + hour + ':' + minute + ':' + second;
}

var PhotoApplication = K.PhotoApplication = function(el, options) {
    var defaultOptions = {
        btns: '',
        source: '',
        _token: '',
        scroll: true 
    };
    this.El = el;
    this.options = $.extend(defaultOptions, options);
    this.initialize();
} 

PhotoApplication.prototype = {
    constructor: PhotoApplication,
    initialize: function() {
        var options = this.options;
        this.El.html('<div class="operate-con" id="operateCon"></div>' + 
            '<div class="photos-con" id="photosCon"></div>'
            );
        this.lock = false;
        this.createPhotosManager();
        this.createOperateBar();
        this.source();
        if (options.scroll) 
            this.setScrollHandler();        
    },
    source: function() {
        if (this.lock) return;
        this.lock = true;
        var _this = this;
        var params = {};
        var lastData = this.photosManager.getLastData();
        params.activity = activityId;
        params.creator_type = (this.options.type == 'master') ? 0 : 1;
        if (lastData && lastData.id) {
            params.last_id = lastData.id;
        }
        if (lastData && lastData.created_at) {
            params.last_created_at = lastData.created_at;
        }

        syncPhoto.list(params, function(resp) {
            _this.lock = false;
            if (resp.code == 0) {
                if (resp.images.length == 0) {
                    _this.lock = true;
                } else {
                    _this.photosManager.append(resp.images);
                    if (window.innerHeight >= $(document).height()) {
                        _this.source();
                    }
                }
            } else {
                tip(resp.message || '获取列表出错！');
            }
        })
    },
    getSelectsIds: function() {
        var selectPhotos = this.photosManager.getSelectsData();
        var images = [];
        $.each(selectPhotos, function(index, photoData) {
            images.push(photoData.id);
        })
        return images;
    },
    createOperateBar: function() {
        var options = this.options,
            photosManager = this.photosManager,
            _this = this;
        var operateBar = this.operateBar = new photos.OperateBar({
            btns: options.btns,
            _token: options._token,
            activity: activityId
        });

        this.El.find('#operateCon').html(operateBar.El);
        operateBar.on('upload', function(data) {
            photosManager.appendBefore(data);
        });

        operateBar.on('batch', function() {
            photosManager.setStatus('batch');
        });

        operateBar.on('delete', function() {
            var images = _this.getSelectsIds();
            photosManager.doDelete();
            var dialog = DialogUi.loading('正在删除中...');
            syncPhoto.remove({
                _token: options._token,
                activity: activityId,
                images: images
            }, function(resp) {
                dialog.close();
                if (resp.code == 0) {
                    photosManager.doDelete();
                    tip('照片删除成功');
                } else {
                    tip(resp.message || '删除照片出错了!');
                }
            })
        });

        operateBar.on('finish', function() {
            photosManager.setStatus('normal');
        });

        operateBar.on('pass', function() {
            var albums = _this.getSelectsIds();
            syncPhoto.approve({
                activity: activityId,
                images: albums,
                _token: options._token
            }, function(resp) {
                if (resp.code == 0) {
                    photosManager.doDelete();
                } else {
                    tip(resp.message || '操作出错了!');
                }
            })
        });

    },
    createPhotosManager: function() {
        var photosManager = this.photosManager = new photos.PhotosManager({}),
            _this = this;
        this.El.find('#photosCon').html(photosManager.El);
        photosManager.on('select', function(array) {
            _this.operateBar.setNums(array.length);
        });
    },
    setScrollHandler: function() {
        var _this = this;
        var documentEl = $(document);
        documentEl.scroll(util.throttle(function(){
            var scrollTop = documentEl.scrollTop(),
                bodyHeight = documentEl.height();                
            if (scrollTop + 100 + window.innerHeight > bodyHeight) {
                //console.log('scrollTop + 100 + window.innerHeight:' + (scrollTop + 100 + window.innerHeight) + 'bodyHeight:' + bodyHeight);
                _this.source(); 
            }
        }, 200));
    }
}

Observe.make(PhotoApplication.prototype);

})()