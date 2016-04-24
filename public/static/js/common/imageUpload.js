/**
 * image uploader
 * by pheart
 */
$(function() {
var Observe = K.Observe,
    Class = K.Class;
    DialogUi = K.dialogUi;

var ImageUploader;

/**
 * image uploader ui compontent  
 * depend on webuploader
 * @parms {Object} options
        - text {String} 
        - class {String}
        - deleteMode {String} light | heavy  default is light, only destory data,  when use heavy, delte data and element.
 */
K.imageUploader = ImageUploader = Class.create({
    initialize: function(options) {
        var defaultOptions = {
            data: '',
            text: '上传',
            deleteMode: 'light'  // light | heavy
        };
        this.options = $.extend(defaultOptions, options || {});
        this.El = $('<div class="ui-uploadThumb"></div>');
        if (this.options.class) {
            this.El.addClass(options.class);
        }
        this.id = new Date().getTime();
        this.initState();
        this.render();
        this.setWebUploader();
        this.setEvents();
    },
    initState: function() {
        this.state = {
            status: 'normal' // normal | upload
        };
    },
    setState: function(stateObj) {
        this.state = $.extend(this.state, stateObj);
        this.render();
    },
    setData: function(data) {
        this.options.data = data;
    },
    getData: function() {
        return this.options.data;
    },
    render: function() {
        var options = this.options;
        var htmlString;
        if (this.state.status == 'normal') {
            htmlString = '<div class="ui-uploadThumb-link" href="javascript:;">' +
                    '<div class="ui-uploadThumb-wrap">' +
                        '<div class="ui-uploadThumb-upload">' +
                            '<span class="ui-uploadThumb-icon"><i class="icon iconfont"></i></span>' +
                            '<span class="ui-uploadThumb-text">'+ options.text +'</span>' +                           
                        '</div>' +
                    '</div>' +
                '</div>' ;
            if (this.El.hasClass('ui-uploadThumb-has')) {
                this.El.removeClass('ui-uploadThumb-has');
            }
        } else if (this.state.status == 'upload') {
            htmlString = '<div class="ui-uploadThumb-link " href="javascript:;">' +
                    '<div class="ui-upload-img-wrap">' +
                        '<img src="'+ options.data.image_url +'" alt="">' +
                    '</div>' +
                    '<div class="ui-uploadThumb-option">' +
                        '<a href="javascript:;" class="close"><i class="icon iconfont"></i></a>' +
                    '</div>' +
                '</div>' ; 
            this.El.addClass('ui-uploadThumb-has');
        } 
        this.El.html(htmlString);
    },
    setWebUploader: function() {
        var _this = this;
        var uploader = this.uploader = WebUploader.create({
            formData: {_token: _this.options._token},
            fileVal: 'image',
            auto: true,
            swf: '/static/plugins/webuploader/Uploader.swf',
            server: '/community/image/tmp/upload',
            pick: _this.El.find('.ui-uploadThumb-icon'),
            accept: {
                title: 'Images',
                extensions: 'jpg,jpeg,png',
                mimeTypes: 'image/*'
            }
        });

        uploader.on('uploadSuccess', function(file, resp) {
            if (resp.code === 0) {
                var curData = {image_url: resp.image_url, image_id: resp.image_id};
                _this.setData(curData);
                _this.setState({status: 'upload'});
                _this.trigger('uploadFinish', _this);
            } else {
                DialogUi.alert(resp.message || '上传失败');
            }
        });

        uploader.on('uploadError', function(file) {
            DialogUi.alert('上传失败');
        });

    },
    setEvents: function() {
        var _this = this;
        this.El.on('click', '.close', function() {
            _this.setData({});
            _this.setState({status: 'normal'});
            _this.setWebUploader();
            if (_this.options.deleteMode == 'heavy') {
                //_this.El.remove();
                _this.trigger('close', _this);
            }
        });
    }
});

// set it to pub/sub 
Observe.make(ImageUploader.prototype);

});