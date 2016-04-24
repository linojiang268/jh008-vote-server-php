<script type="text/template" id="notice_dialog_template">
    <div class="notice-dialog">
        <p class="notice-dialog-error-tip" id="noticeSelError"></p>
        <div class="notice-dialog-item">
            <span class="notice-dialog-text">类型</span>
            <div class="notice-dialog-wrap">
                <div class="radioSels clearfix" id="noticeWay">
                    <div class="radio-wrap sel">
                        <input type="radio" checked="checked" name="pushWay" value="1">
                        <label><i class="icon iconfont"></i>消息推送</label>
                    </div>
                    <div class="radio-wrap">
                        <input type="radio" name="pushWay" value="2">
                        <label><i class="icon iconfont"></i>短信</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="notice-dialog-item">
            <span class="notice-dialog-text">类型</span>
            <div class="notice-dialog-wrap">
                <div class="limitText" id="pushWayLimit">
                    <div class="textarea-con">
                        <textarea id="noticeTextarea" class="limitText-ta" placeholder="可输入128个字"></textarea>
                        <span class="text-tip">0/128</span>                      
                    </div>
                </div> 
                <div class="limitText" id="smsWayLimit" style="display:none">
                    <div class="textarea-con">
                        <textarea id="msgTextarea" class="limitText-ta" placeholder="可输入60个字"></textarea>
                        <span class="text-tip">0/60</span>                      
                    </div>
                </div>
            </div>
        </div>

        <div class="notice-dialog-item">
            <span class="notice-dialog-text">对象</span>
            <div class="notice-dialog-wrap">
                <div class="notice-sel-con">
                    <div class="clearfix">
                        <div class="unsel-main">
                            <div class="p15">
                                <div class="search-wrap">
                                    <input type="text" class="search" id="search" placeholder="请输入查找关键字">
                                    <a class="del-btn"><i class="icon iconfont" id="delSearch"></i></a>
                                </div>
                                <div class="sel-wrap">
                                    <div class="checkboxSels clearfix sel-all">
                                        <div class="checkbox-wrap">
                                            <input type="checkbox" name="selAll" id="selAll" value="1">
                                            <label><i class="icon iconfont"></i>全部</label>
                                        </div>
                                    </div>
                                    <div id="groupNavCon">
                                        <div id="groupList"></div>
                                        <div id="searchList">
                                            <ul class="member-nav" id="searchNav"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mid-main">
                            <span class="mid-show"><i class="icon iconfont"></i></span>
                        </div>
                        <div class="sel-main">
                            <div class="sel-main-wrap">
                                <p class="sel-num-tip">已选：<span id="selNumText"></span></p>
                                <div class="sel-has-con">
                                    <ul class="member-nav" id="hasSelNav"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="send-notice-c">
                        <a href="javascript:;" class="button button-orange button-m" id="sendBtn">发送</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</script>