$(function(){
  var DialogUi = K.dialogUi,
      server = K.server;

  var _token = $('input[name="_token"]').val();
  // confirm create || update dialog
  function confirm (text, cancelText, okText, okCallback, cancelCallback) {
    DialogUi.confirm({
      text: text || '确定要创建社团资料',
      cancelText: cancelText || '返回修改',
      okText: okText || '确认创建',
      okCallback: function() {
        okCallback && okCallback();
      },
      cancelCallback: function() {
        cancelCallback && cancelCallback();
      }
    })
  }

  // tip 
  function tip (msg) {
    DialogUi.alert(msg || '');
  }

  // form validator
  function setForm() {
    var rules = {
      name: "required",
      contact: "required",
      contactPhone: "telphone",
      email: "required email",
      infor: "required"
    },
      messages = {
        name: "团名不能为空",
        contact: "联系人不能为空",
        contactPhone: "电话号码不能为空",
        //isPhone: '请输入电话号码',
        email: "请输入正确格式的邮箱地址",
        infor: "团队简介不能为空"
      };
    if (mode == 'create') {
      rules.logoInput = 'required';
      messages.logoInput = '请上传社团Logo';
    }
    $('#assnForm').validate({
        rules: rules,
        messages: messages,
        validClass: "success",
        keyup: false,
        errorPlacement: function(error, element) {
          if (element.attr('id') == 'infor') {
            $('#limitText').after(error).addClass('error');
          }else {
            error.appendTo(element.parent()); 
          }
        },
        submitHandler: function(form) {
          var result = {};
          result.city = $.trim($('#city').val());
          result.name = $.trim($('#name').val());
          result.email = $.trim($('#email').val());
          result.logo_id = $.trim($('#logoInput').val());
          result.address = $.trim($('#address').val());
          result.contact_phone = $.trim($('#contactPhone').val());
          result.contact = $.trim($('#contact').val());
          result.introduction = $.trim($('#infor').val());
          result._token = _token;
          if (mode == 'create') {
            // 创建社团确认弹出框
            //confirm('确定要创建社团资料', '返回修改', '确认创建', function() {
              // 创建社团请求
              result.logo_id = $('#target').attr('src');
              result.crop_start_x = Number( imgCrop_start_x );
              result.crop_start_y = Number( imgCrop_start_y );
              result.crop_end_x = Number(imgCrop_end_x);
              result.crop_end_y = Number(imgCrop_end_y) ;
              server.createTeam(result, function(resp) {
                if (resp.code == 0) {
                  location.reload();
                } else {
                  tip(resp.message || '创建社团失败');
                }
              })               
            //})
          } else if (mode == 'update') {
            result.team = $.trim($('#team').val());
            // 修改社团确认弹出框
            //confirm('确定要修改社团资料', '返回修改', '确认修改', function() {
              // 更新社团资料请求
              result.logo_crop = 1;
              result.logo_id = $('#target').attr('src');
              if ( !imgCrop_end_x && !imgCrop_end_y ) {
                result.logo_crop = 0;
              }else {
                result.logo_crop = 1;
                result.crop_start_x = Number( imgCrop_start_x );
                result.crop_start_y = Number( imgCrop_start_y );
                result.crop_end_x = Number(imgCrop_end_x);
                result.crop_end_y = Number(imgCrop_end_y);
              }
              server.updateTeam(result, function(resp) {
                if (resp.code == 0) {
                  location.reload();
                } else {
                  tip(resp.message || '修改社团失败');
                }
              })           
            //})
          }
        },
        success: function(label) {
          if (label.siblings('#limitText').length) {
            $('#limitText').removeClass('error');
          }
        }
    })
  }

  var MAX_SIZE = 500 * 1024,
      uploader;
  // config logo upload 
  function setLogoUploader() {
/*    var target = $('.logo-upload-has');
    if (target.length) {
      target.removeClass('logo-upload-has');
    }*/
    
    uploader = WebUploader.create({
        formData: {_token: _token},
        fileVal: 'image',
        auto: true,
        swf: '/static/plugins/webuploader/Uploader.swf',
        server: '/community/image/tmp/upload',
        pick: '#selectLogo',
        accept: {
            title: 'Images',
            extensions: 'jpg,jpeg,bmp,png',
            mimeTypes: 'image/*'
        }
    })

    uploader.on( 'beforeFileQueued', function( file ) {
      if (file.size > MAX_SIZE) {
        DialogUi.message('上传Logo不能超过500K');
        return false;
      }
      if ($('#target').attr('src')) {
        $('#cancelBtn').trigger('click');
      }
    });

    uploader.on('uploadProgress',function(file,p){
      var percent = Number(Number(p).toFixed(4)) * 100,
          $percent =  $('#progress-percent'),
          $parent = $('.upload-progress-w');
      $parent.removeClass('hide');
      $percent.text( percent.toFixed(0) + '%' );

      if ( percent == 100 ) {
        $percent
          .siblings('p')
          .text('上传完成');
      }
    });

    uploader.on('uploadSuccess', function(file, resp) {
        if (resp.code == 10000) {
          tip(resp.messages || '上传Logo出错了!');
        } else if (resp.code == 0) {
          $('.logo-upload').addClass('logo-upload-has');
          //隐藏进度层
          $('.upload-progress-w')
            .addClass('hide')
            .children('#progress-percent')
            .text('')
            .siblings('p')
            .text('上传中请稍后...');

          // 上传成功后，显示图片
          $('#target').attr('src', resp.image_url);
          // 显示小图
          $('.preview-box-con img').attr('src',resp.image_url);

          $('#logoInput').val(resp.image_url).trigger('keyup');
        }
    });

    uploader.on('error', function(e) {
        if ( e == 'Q_TYPE_DENIED' ) {
          tip('上传的图片类型暂不支持剪裁，请选择其它图片上传，目前支持.jpg ，.jpeg ，.bmp ，.png后缀类型的图片。');
        }
    });

    uploader.on('uploadError', function(file) {
      tip('上传Logo出错了!');
    });
    
  }

   
  var logoEl, targetEl, logoCon;
  function setLogoHandler() {
    logoEl = $('#logo'),
    targetEl = $('#target'),
    logoCon = $('.logo-upload-content');

    logoCon.hover( function(){
      logoCon.addClass('logo-upload-active');
    }, function() {
      logoCon.removeClass('logo-upload-active');
    })

    logoEl.change(function() {
      var parent = $('#logoForm');
      parent.submit();
    })
  }

  if (window.mode) {
    setForm();
    setLogoUploader();
    setLogoHandler();
  }

  $('.inspect').bind('click', function(e) {
    var target = $(e.delegateTarget),
        id = target.attr('data-id');
    server.inspect({request: id, _token: _token}, function(resp) {
      if (resp.code == 0) {
        target.parent('.ui-infor').remove();
      } else {
        tip(resp.messages || '服务器出错了');
      }
    })
  });

  $('#citySelect').on('click', 'a', function(e) {
    var target = $(e.target);
    var cityId = target.attr('data-id');
    $('#city').val(cityId);
  })

  //如果mode为create 页面上的部分菜单disabled
  if (mode == 'create') {
    var text = _status ? "社团正在审核中，请耐心等待1-2个工作日" : "请您先创建社团！";
    $('.menu-warp').on('click', '.menu ul a', function(event) {
      event.preventDefault();
      DialogUi.alert(text);
      return false;
    });

    $('.ui-tab2').on('click', 'ul a', function(event) {
      event.preventDefault();
      DialogUi.alert(text);
      return false;
    });
  }
// });

// $(function() {
  // var DialogUi = K.dialogUi,
  //     server = K.server;
  var imgWidth_origin  = 0,
      imgHeight_origin = 0,
      imgWidth_crop    = 0,
      imgHeight_crop   = 0,
      imgCrop_start_x  = 0,
      imgCrop_start_y  = 0,
      imgCrop_end_x    = 0,
      imgCrop_end_y    = 0,
      Jcop_api ;

  function setCrop() {
    // Create variables (in this scope) to hold the API and image size
    var jcrop_api,
        boundx,
        boundy,

        // Grab some information about the preview pane
        $preview = $('.preview-box'),
        $pcnt = $('.preview-box-con'),
        $pimg = $('.preview-box-con img'),
        // 200x200
        xsize = $pcnt.width(),
        ysize = $pcnt.height();
    
    // 设置 jcrop
    $('#target').Jcrop({
      // 选框改变时的事件 
      onChange: updatePreview,
      // 选框选定时的事件
      onSelect: updatePreview,
      // 选框宽高比 说明：width / height
      aspectRatio: xsize / ysize,
      allowSelect: false,
      minSize: [60,60]
    },function(){
      // Use the API to get the real image size
      // 获取图片实际尺寸，格式：[w,h]
      var bounds = this.getBounds();
      // 图片实际的宽
      boundx = bounds[0];
      // 图片实际的高
      boundy = bounds[1];
      // Store the API in the jcrop_api variable
      // jcrop_api = this;
      Jcop_api = this;
      //缓存图片实际的宽高
      imgWidth_crop = boundx;
      imgHeight_crop = boundy;

      //获取图片显示的尺寸
      var viewBounds = this.getWidgetSize(),
          vx = viewBounds[0],
          vy = viewBounds[1];
      if ( vx > vy ) {
        var w = Math.floor( 0.8 * vy );
        var y = Math.floor( (vy - w) / 2 ),
            x = Math.floor( ( vx - w ) / 2 );
      }else {
        var w = Math.floor( 0.8 * vx );
        var x = Math.floor( (vx - w) / 2 ),
            y = Math.floor( ( vy - w ) / 2 );
      }
      this.setSelect([x , y , x + w , y + w]);
      this.setOptions({
            bgColor: 'white',
            bgOpacity: 0.5
          });
      // Move the preview into the jcrop container for css positioning
      //$preview.appendTo(jcrop_api.ui.holder);
    });

    function updatePreview(c)
    {
      if (parseInt(c.w) > 0)
      {
        var rx = xsize / c.w;
        var ry = ysize / c.h;
        
        //保存起始点与终点的坐标
        var BL1 = imgWidth_origin / imgWidth_crop,
            BL2 = imgHeight_origin / imgHeight_crop;
        imgCrop_start_x = Math.round( c.x * BL1 );
        imgCrop_start_y = Math.round( c.y * BL2 );
        imgCrop_end_x = Math.round( c.x2 * BL1 );
        imgCrop_end_y = Math.round( c.y2 * BL2 );

        $pimg.css({
          width: Math.round(rx * boundx) + 'px',
          height: Math.round(ry * boundy) + 'px',
          marginLeft: '-' + Math.round(rx * c.x) + 'px',
          marginTop: '-' + Math.round(ry * c.y) + 'px'
        });
      }
    };
}

    var img1 = $('#target'),
        logoCon = $('.logo-upload');   
    var img = new Image();
    img.src = $.trim(img1.attr('src'));
    img.id = 'target';
    $('.logo-upload-main').html(img);
    var $w = img.width;    
    var $h = img.height;

  function calcAndSetCrop(img) {
    var w = img.width(),
      h   = img.height(),
      bl  = w / h,
      w1  = 470,
      h1  = 340,
      bl1 = w1/h1, 
      w0, 
      h0;

    //缓存图片的原始宽高
    imgWidth_origin = w;
    imgHeight_origin = h;

    if (w > w1 || h > h1) {
      if (bl >= bl1) {
        w0 = 470;
        h0 = Math.floor(w0 / bl);
      } else {
        h0 = 340;
        w0 = bl * h0;
      }
    }
    img.width(w0);
    img.height(h0);
    //设置剪裁对象
    setCrop();
  } 

    // 图片上传成功后，重新计算宽高以用来剪裁
    $(img).load(function(){
      if ( logoCon.hasClass('logo-update') ) {
        return;
      }
      var img = $(this);
      // 预览时，将预览logo设为200x200
      if ( $('.logo-upload-main').hasClass('logo-crop-preview') ) {
        img
          .width(200)
          .height(200);
          return;
      }
      calcAndSetCrop(img);
    });

    //保存预览
    var $logoUploadCon = $('.logo-crop').find('.logo-upload');
    $('.logo-upload-btns').on('click', '#saveBtn', function(event) {
      if ( !$('#target').attr('src') ) {
        DialogUi.message('请先上传一张图片并裁剪',2500);
        return;
      }
      if ( $(this).hasClass('disable') ) {
        return;
      }else {
        $(this)
          .addClass('disable')
          .parent()
          .addClass('pre')
      }
      $('#selectLogo').hide();
      $logoUploadCon
        .hide()
        .siblings('.logo-preview')
        .addClass('moveStart')
        .animate({
          marginLeft: '0'
        },500, function() {
          $(this).removeClass('moveStart');
        });
      $('#cancelBtn').text('编辑');
    });

    //取消并重新编辑
    $('.logo-upload-btns').on('click', '#cancelBtn', function(event) {
      var $sb = $('#saveBtn'),
          $cb = $('#cancelBtn');
          $target = $('#target');

      if ( $sb.hasClass('disable') ) {//编辑
          $sb.removeClass('disable');  
          $cb
            .text('清除')
            .parent()
            .removeClass('pre')
      }else if ( $target.attr('src') ) {//清除
          $target
            .attr('src','')
            .removeAttr('style')
            .removeAttr('width')
            .removeAttr('height');
          $('#selectLogo').show();
          $('.preview-box-con > img')
            .attr('src','')
            .removeAttr('style');

          uploader.reset();
          Jcop_api.destroy();
          return;
      }else return;
      //点击编辑时的动画
      $logoUploadCon
        .siblings('.logo-preview')
        .animate({
          marginLeft: '562px'
        },500, function() {
          $logoUploadCon.show(0,function(){
            logoCon.removeClass('logo-update');
            calcAndSetCrop($('#target'));
          });
          $('#selectLogo').show();
          $('input[type="file"]').siblings('label').removeAttr('style').css({"width":"120px","display":"block","opacity":0,"height":"39px","cursor":"pointer"}).parent().css({'width':'120px','height':'39px'});
          $(this).removeAttr('style');
        });
    });

  if ($('#infor').length) {
    $('#infor').trigger('keyup');
  }
});
