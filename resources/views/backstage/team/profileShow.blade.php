<div class="ui-form" name="" method="post" action="#" id="assnForm">
    <div class="ui-form-item">
        <label for="" class="ui-label">社团名称:</label>
        <span class="ui-form-text"> {{ $team->getName() }} </span>
    </div>
    <div class="ui-form-item">
        <label for="" class="ui-label">
            团队LOGO:
        </label>
        <div class="ui-form-item-wrap">
            <p class="logo-tip">支持JPG、PNG文件，最大500k。图片尺寸为200*200px。</p>
            <div class="logo-upload-con">
                <div class="logo-upload-content">
                    <div class="logo-upload logo-crop-preview-w">
                        <div class="logo-upload-wrap">
                            <div class="logo-upload-main logo-crop-preview">
                                <img id="target" src="{{ $teamLogoUrl }}" alt="">
                            </div>                              
                        </div>
                    </div>                            
                </div>
            </div>
        </div>
    </div>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            城市:
        </label>
        <span class="ui-form-text"> {{ $team->getCity()->getName() }}  </span>
    </div>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            地址:
        </label>
        <span class="ui-form-text"> {{ $team->getAddress() }} </span>
    </div>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            联系人:
        </label>
        <span class="ui-form-text"> {{ $team->getContact() }} </span>
    </div>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            联系电话:
        </label>
        <span class="ui-form-text"> {{ $team->getContactPhone() }} </span>
    </div> 

    <div class="ui-form-item">
        <label for="" class="ui-label">
            电子邮箱:
        </label>
        <span class="ui-form-text"> {{ $team->getEmail() }} </span>
    </div>

    <div class="ui-form-item">
        <label for="" class="ui-label">
            团队宣言:
        </label>
        <div class="ui-form-item-wrap">
            <span class="ui-form-text"> {{ $team->getIntroduction() }} </span>
        </div>
    </div>      
</div>