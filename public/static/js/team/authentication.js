$(function() {
    var DialogUi = K.dialogUi,
        server = K.server;
    var ImageUploader = K.imageUploader;

    var _token = $('input[name="_token"]').val();

    var TeamAuthentication = (function() { // 认证资料
        var list = [];

        function deleteImageUploader(iu) {
            for (var i = list.length - 1; i >= 0; i--) {
                if (list[i].id == iu.id) {
                    list.splice(i, 1);
                }
            }
            if (list.length < 1) {
                createImageUploader();
            }
        }

        function createImageUploader() {
            if (list.length >= 8) 
                return false;
            var imageUploader = new ImageUploader({
                _token: _token,
                text: '上传社团资料',
                deleteMode: 'heavy'
            });

            imageUploader.on('close', function(iu) {
                deleteImageUploader(iu);      
            });

            imageUploader.on('uploadFinish', function(iu) {
                createImageUploader();
            })

            list.push(imageUploader);
            $('#inforsList').append(imageUploader.El);
        }
        
        function _render() {
            createImageUploader();
        }

        function _getData() {
            if (list.length <= 0 || (list.length == 1 && !list[0].getData() && !list[0].getData().image_url)) {
                DialogUi.alert('请上传至少一张社团资料');
                return false;
            }
            var result = [];
            $.each(list, function(index, iu) {
                var item = {};
                if (iu.getData().image_url) {
                    item.type = 2;
                    item.certification_id = iu.getData().image_url;
                    result.push(item);                    
                }
            })
            return result;
        } 

        return {
            render: _render,
            getData: _getData
        }
    })()

    var CartAuthentication = (function() {
        var positive, negative, cardsListEl = $('#cardsList');

        function createImageUploader(text) {
            var imageUploader = new ImageUploader({
                _token: _token,
                text: text
            });

            cardsListEl.append(imageUploader.El);
            return imageUploader;
        }

        function createPositive() {
            positive = createImageUploader('正面照');
        }

        function createNegative() {
            negative = createImageUploader('反面照');
        }

        function _render() {
            createPositive();
            createNegative();
        }

        function _getData() {
            var result = [];
            if (!positive || (!positive.getData() && !positive.getData().image_url)) {
                DialogUi.alert('请上传身份证正面照');
                return false;
            }
            if (!positive || (!negative.getData() && !negative.getData().image_url)) {
                DialogUi.alert('请上传身份证反面照');
                return false;
            }
            result.push({type: 0, certification_id: positive.getData().image_url});
            result.push({type: 1, certification_id: negative.getData().image_url})
            return result;
        }

        return {
            render: _render,
            getData: _getData
        }
    })()

    var page = {
        initialize: function() {
            if (window.status == 0) {
                TeamAuthentication.render();
                CartAuthentication.render();
                $('#submitAuth').click(function() {
                    var teamDatas, cartDatas;
                    if ((teamDatas = TeamAuthentication.getData()) && (cartDatas = CartAuthentication.getData())) {
                        var datas = teamDatas.concat(cartDatas);
                        //console.log(datas);
                        server.updateCertifications({certifications: datas, _token: _token}, function(resp) {
                            if (resp.code == 0) {
                                location.reload();
                            } else {
                                DialogUi.alert(resp.message || '提交认证失败');
                            }
                        })                        
                    }

                })
            }
        }
    }

    page.initialize();

})