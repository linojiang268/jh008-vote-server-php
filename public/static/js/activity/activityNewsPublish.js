$(function(){
    //引入库
	var Dialog = K.dialogUi,
        Server = K.server,
        dropdownPage = K.util.dropdownPage;
    //必要的变量申明
    var ImageUploader = K.imageUploader;
    var _token = $("input[name='_token']").val();
    var activeCoverEl = $('#news-cover');
    var newsId = $("#newsId").val();

    /**
     * 初始化下拉分页
     */
    var dropPage = (function(){
        function renderDropDownPage(){
            var dropdownUl = $(".ui-select1 .dropdown-menu");
            var pageNation = $(".ui-select1 .dropdown-page-w .page-index-w"); 
            var parms = {};
                parms.page = 1;
                parms.team_id = 1;
                Server.listdata(parms, function(resp){
                    if (resp.code == 0) {
                        //初始化下拉分页
                        //追加所有和只与社团有关 的选项
                        resp.activities.unshift({'id':'','title':'<span style="color: red;">-- 发布只与社团有关的资讯</span>'});
                        dropdownPage.init(resp.activities,dropdownUl,pageNation);
                        pageNation.parent().css("top",dropdownUl.height()+34);
                    } else {
                        Dialog.alert(resp.message || '查询数据列表出错');
                    }
                })

            $(".ui-select1").on('click','.dropdown-menu a',function(e){
                e.stopPropagation();
                var activity_id = $(this).data('aid') || '';
                $(".ui-select-text").data("aid",activity_id);
            });
        }
        return {
            render: renderDropDownPage
        }
    })();

    /**
     * 初始化上传插件
     */
	var activityCoverUpload = (function(){
        var img_url = null;

		function createImageUploader(text) {
            var imageUploader = new ImageUploader({
                _token: _token,
                text: text
            });
            activeCoverEl.append(imageUploader.El);
            imageUploader.on('uploadFinish', function(resp) {
                img_url = resp.options.data.image_url;
            });
            return imageUploader;
        }

        function updateUpload(url){
            var options ={
                    _token: _token,
                    text: url ? '当前封面':'未设置封面图片',
                    data: {
                        image_url: url
                    }
                };
            var imageUploader = new ImageUploader(options);
            imageUploader.on('uploadFinish', function(resp) {
                img_url = resp.options.data.image_url;
            });
            activeCoverEl.append(imageUploader.El);
            // imageUploader.setData(curData);
            //如果有上传的封面则改变状态
            if ( options.data.image_url ) {
                imageUploader.setState({status: 'upload'});
            }
            return imageUploader;
        }

        function getImgUrl(){
            return img_url;
        }
        return {
            getCoverUrl: getImgUrl,
        	render: createImageUploader,
            renderUpdate: updateUpload
        }
	})();

	var Page = {
		initialize : function(text){
            if (!newsId) {
                dropPage.render();
                activityCoverUpload.render(text);
            }
            this.pageInit();
        },
        pageInit : function(){
            
            if (newsId) {
                var url = $("#coverUrl").val()
                activityCoverUpload.renderUpdate(url);
            }

            $("#newsSubmit").on('click', function(event) {
                event.preventDefault();
                var parms = {};
                if (!newsId) {//如果ID不存在，则发布新资讯
                    /*if ( $(".ui-select-text").text() == '请选择对应的活动' ) {
                        Dialog.alert('请选择相应的活动');
                        return;
                    }*/

                    parms.title = $("#newstitle").val();
                    parms.content = ue.getContent();
                    if ($(".ui-select-text").data("aid")) {
                        parms.activity_id = Number($(".ui-select-text").data("aid"));
                    }
                    parms.cover_url = activityCoverUpload.getCoverUrl();
                    // parms.team = Number($(".ui-select-text").data("tid")) || Number(sessionStorage.getItem('tid'));
                    parms._token = _token;
                    DialogUi.loading("发布资讯中");
                    Server.publishNews(parms,function(resp){
                        if ( resp.code == 0 ) {
                            Dialog.alert(resp.message,function() {
                                window.location.href='/community/activity/news/list';
                            });
                        }else {
                            Dialog.alert(resp.message);
                        }
                    });
                }else {//否则为编辑资讯
                    parms = {};
                    parms.title = $("#newstitle").val();
                    parms.content = ue.getContent();
                    parms._token = _token;
                    parms.cover_url = activityCoverUpload.getCoverUrl() || url;
                    DialogUi.loading("提交中");
                    Server.updateNews(parms,newsId,function(resp) {
                        if ( resp.code == 0 ) {
                            Dialog.alert(resp.message,function() {
                                window.location.href='/community/activity/news/list';
                            });
                        }else {
                            Dialog.alert(resp.message,function(){
                                window.location.reload();
                            });
                        }
                    });
                }
                
            });
        }
	}; 
    
    Page.initialize("上传活动封面");
});
