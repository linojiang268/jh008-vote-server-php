<form class="ui-form assn-profile-form" name="" method="post" action="#" id="assnForm">
    <div class="ui-form-item">
        <label for="" class="ui-label">
            <span class="ui-form-required">*</span>社团名称:
        </label>
        <input name="name" id="name" class="form-control w200" type="text" value="@if ($team){{ $team->getName() }}@endif" >
    </div>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            <span class="ui-form-required">*</span>所在城市:
        </label>
        <div class="ui-form-item-wrap">
            <div class="ui-select ui-select-middel">
                @if ($team) 
                    <span class="ui-select-text">{{ $team->getCity()->getName() }}</span> 
                @elseif ($cities && $cities[0])
                    <span class="ui-select-text">{{ $cities[0]->getName() }}</span>
                @endif
                <span class="tri-down"></span>
                <ul class="dropdown-menu" id="citySelect" role="menu" style="display: none;">
                    @if ($cities)
                        @foreach ($cities as $city)
                            <li><a href="javascript:;" data-id="{{ $city->getId() }}">{{ $city->getName() }}</a></li>
                        @endforeach
                    @endif
                </ul>
            </div>
            @if ($team) 
                <input type="hidden" name="city" id="city" value={{ $team->getCity()->getId() }}>
            @elseif ($cities && $cities[0])
                <input type="hidden" name="city" id="city" value={{ $cities[0]->getId() }}>
            @endif
        </div>
    </div>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            <span class="ui-form-required">*</span>团队LOGO:
        </label>
        <div class="ui-form-item-wrap">
            <p class="logo-tip">支持JPG、PNG文件，最大500k。图片尺寸为200*200px，请拖动和缩放后确认。</p>
            <!-- -------------------------------- -->
            <!-- <div class="logo-upload-con">
                <div class="logo-upload-content">
                    <div class="logo-upload logo-upload-has">
                        <a href="javascript:;" id="selectLogo" class="button button-m button-orange">上传LOGO<i class="icon iconfont"></i></a>
                        <div class="logo-upload-wrap">
                            <div class="logo-upload-main">
                                <img id="target" src="@if ($team) {{ $team->getLogoUrl() }} @endif" alt="">
                            </div>                              
                        </div>
                        <div class="logo-upload-wrap logo-upload-hover"></div>
                    </div>                            
                </div>
            </div> -->
            <!-- -------------------------------- -->
            <div class="logo-upload-con logo-crop">
                <div class="logo-upload-wrap">
                    <div class="logo-upload-top clearfix">
                        <div class="logo-upload @if ( $team && $team->getLogoUrl() ){{"logo-update hide"}} @endif">
                            <div class="logo-upload-main">
                                <img id="target" src="@if ($team) {{ $team->getLogoUrl() }} @endif" alt="">
                            </div>
                            <div class="upload-progress-w hide">
                                <p id="progress-percent"></p>
                                <p>上传中请稍等...</p>
                            </div>
                        </div>
                        <div class="logo-preview">
                            <div class="logo-preview-wrap">
                                <p class="preview-tip">LOGO预览</p>
                                <div class="preview-box">
                                    <div class="preview-box-con">
                                        <!-- <img src="http://p6.qhimg.com/dmt/490_350_/t013fcbe776faa1d90c.jpg" alt=""> -->
                                        <img style="width:200px;" src="@if ($team) {{ $team->getLogoUrl() }} @endif" alt="">
                                    </div>
                                </div>
                                <p class="preview-sizetip">200 X 200</p>
                            </div>
                        </div>
                    </div>
                    <div class="logo-upload-btns @if ( $team && $team->getLogoUrl() ) {{"pre"}} @endif">
                        <a href="javascript:;" id="saveBtn" class="button button-orange @if ( $team && $team->getLogoUrl() ) {{"disable"}} @endif">确认</a>
                        <a href="javascript:;" id="cancelBtn" class="button ml15">@if ( $team && $team->getLogoUrl() ) {{"编辑"}} @else {{"清除"}} @endif</a>
                    </div>
                <a @if ( $team && $team->getLogoUrl() ) {{"style=display:none;"}} @endif href="javascript:;" id="selectLogo">选择图片<i class="icon iconfont"></i></a>
                </div>
            </div>
            <!--  -->
            <input type="text" name="logoInput" id="logoInput" class="logo-id-input" value="">
        </div>
    </div>
    
    <div class="ui-form-item">
        <label for="" class="ui-label">
            <span class="ui-form-required">*</span>团队宣言:
        </label>
        <div class="ui-form-item-wrap">
            <div class="w500">
                <div class="limitText" id="limitText">
                    <div class="textarea-con">
                        <textarea name="infor" id="infor" class="limitText-ta" placeholder="可输入100字">@if ($team){{ $team->getIntroduction() }}@endif</textarea>
                        <span class="text-tip">0/100</span>                      
                    </div>
                </div>                      
            </div>
        </div>
    </div>
    
    <p class="some-info-hints">
        以下信息不公开显示，仅用于客服核对联系。
    </p>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            <span class="ui-form-required">&nbsp;</span>地址:
        </label>
        <input name="address" id="address" class="form-control w300" type="text" value="@if ($team){{ $team->getAddress() }}@endif" >
        <span class="ui-form-tip"></span>
    </div>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            <span class="ui-form-required">*</span>联系人:
        </label>
        <input name="contact" id="contact" class="form-control" type="text" value="@if ($team){{ $team->getContact() }}@endif" >
    </div>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            <span class="ui-form-required">*</span>联系电话:
        </label>
        <input name="contactPhone" id="contactPhone" class="form-control contactPhone-input w200" type="text" value="@if ($team){{ $team->getContactPhone() }}@endif" >
        <span class="contactPhone-tip" class="ui-form-tip">&nbsp;&nbsp;请填写手机号或座机号（座机格式：028-85175989）</span>
    </div> 

    <div class="ui-form-item">
        <label for="" class="ui-label">
            <span class="ui-form-required">*</span>电子邮箱:
        </label>
        <input name="email" id="email" class="form-control w200" type="text" value="@if ($team){{ $team->getEmail() }}@endif" >
    </div> 
              
    <div class="ui-form-item">
        <label for="" class="ui-label ui-label-industry">
            &nbsp;
        </label>
        <div class="ui-submit-wrap">
            @if (empty($team))
                <input type="submit" class="ui-form-submit" id="saveProfile" value="创建">
            @else
                <input type="hidden" name="team" id="team" value="@if ($team) {{ $team->getId() }} @endif">
                <input type="submit" class="ui-form-submit" id="saveProfile" value="修改">
            @endif
        </div>
    </div>
</form>