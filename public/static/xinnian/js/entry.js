var maxAllowedPhotos = 5;

var EntryPanel = React.createClass({
    getInitialState: function() {
        return {
            user:{
                images: []
            }
        }
    },

    beforeUploading: function () {
        var parent = $('.file-con', this.refs.entryForm.getDOMNode()),
            uploadingDiv = parent.find('.uploading-div'),
            uploadingFileText = parent.find('.file-text'),
            fileInput = parent.find('input[type="file"]');

        uploadingDiv.show();
        uploadingFileText.css({ marginLeft: '100000px' });
        fileInput.css({ left: '100000px' });
    },

    onUploaded: function () {
        var parent = $('.file-con', this.refs.entryForm.getDOMNode()),
            uploadingDiv = parent.find('.uploading-div'),
            uploadingFileText = parent.find('.file-text'),
            fileInput = parent.find('input[type="file"]');

        uploadingDiv.hide();
        uploadingFileText.css({ marginLeft: '-10px' });
        fileInput.css({ left: '0' });
    },

    componentDidMount: function() {
        var that = this;

        $('#app').scrollTop(0);
        $('input[type="file"]').ajaxfileupload({
          action: '/wap/attendant/image/tmp/upload',
          params: {
            '_token': $('input[name="_token"]').val()
          },
          onComplete: function(response) {
            if (response.code == 0) {
                that.addPhoto(response.image_url);

                if (that.state.user.images.length >= maxAllowedPhotos) {
                    $('.file-con', that.refs.entryForm.getDOMNode()).hide();
                } else {
                    that.onUploaded();
                }
            } else {
                alert(response.messages || '上传照片出错了!');
                that.onUploaded();
            }
          },
          onStart: function() {
            var user = that.state.user;
            if (user.images.length >= maxAllowedPhotos) {
                alert('最多只能上传' + maxAllowedPhotos + '张照片');
                return false;
            }

            that.beforeUploading();
          },
          onCancel: function() {
              that.onUploaded();
          }
        }); 

    },
    addPhoto: function(photo) {
        var user = this.state.user;
        user.images.push(photo);

        this.setState({user: user});
    },

    submitHandler: function() {
        var form = $(this.refs.entryForm.getDOMNode()),
            submitBtn = form.find('#submit-info');
        if (submitBtn.hasClass('disabled')) {
            return;
        }
        submitBtn.addClass('disabled');

        var params = {
            name: $.trim(form.find('input[name="name"]').val()),
            id_number: $.trim(form.find('input[name="id_number"]').val()),
            date_of_birth: $.trim(form.find('select[name="year"]').val()) + '年' + $.trim(form.find('select[name="month"]').val()) + '月',
            gender: form.find('input[name="gender"]:checked').val(),
            height: parseFloat(form.find('input[name="height"]').val()),
            graduate_university: $.trim(form.find('input[name="graduate_university"]').val()),
            degree: $.trim(form.find('input[name="degree"]:checked').val()),
            yearly_salary: $.trim(parseFloat(form.find('input[name="yearly_salary"]').val())),
            work_unit: $.trim(form.find('input[name="work_unit"]').val()),
            mobile: $.trim(form.find('input[name="mobile"]').val()),
            images_url: this.state.user.images,
            talent: $.trim(form.find('textarea[name="talent"]').val()),
            mate_choice: $.trim(form.find('textarea[name="mate_choice"]').val()),
        };

        // perform validation
        if (!params.name) {
            alert('姓名不能为空');
            return submitBtn.removeClass('disabled');
        } else if (!params.id_number) {
            alert('请填写身份证号码');
            return submitBtn.removeClass('disabled');
        } else if (params.id_number.length != 18 && params.id_number.length != 15) {
            alert('请填写合法的身份证号码');
            return submitBtn.removeClass('disabled');
        } else if (!params.gender) {
            alert('请选择性别');
            return submitBtn.removeClass('disabled');
        } else if (isNaN(params.height) || params.height <= 0) {
            alert('请填写正确的身高');
            return submitBtn.removeClass('disabled');
        } else if (!params.graduate_university) {
            alert('请填写毕业院校');
            return submitBtn.removeClass('disabled');
        } else if (!params.degree) {
            alert('请选择学历');
            return submitBtn.removeClass('disabled');
        } else if (isNaN(params.yearly_salary) || params.yearly_salary < 0) {
            alert('请填写年薪');
            return submitBtn.removeClass('disabled');
        } else if (!params.work_unit) {
            alert('请填写工作单位');
            return submitBtn.removeClass('disabled');
        } else if (!/^(((17[0-9]{1})|(13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/.test(params.mobile)) {
            alert('请填写正确的手机号码');
            return submitBtn.removeClass('disabled');
        } else if (params.images_url.length < 1) {
            alert('请至少上传1张个人照片');
            return submitBtn.removeClass('disabled');
        } else if (params.images_url.length > maxAllowedPhotos) {
            alert('最多只能上传' + maxAllowedPhotos + '张个人照片');
            return submitBtn.removeClass('disabled');
        } else {
            K.server.enroll(params, function(resp) {
                submitBtn.removeClass('disabled');
                if (resp.code == 0) {
                    K.aModal({
                        title: '提交成功',
                        content: '<p class="tl">提交成功。我们将尽快审核您的报名信息，请保持通讯畅通。</p>',
                        okCallback: function() {
                            location.hash = '/home'
                        }
                    });
                } else {
                    alert(resp.message || '报名申请提交失败');
                }
            });
       }
    },

    onIdNumberChanged: function () {
        var form = $(this.refs.entryForm.getDOMNode());
        var idNumber = $.trim(form.find('input[name="id_number"]').val());
        if (idNumber.length == 18) {
            var year = idNumber.substr(6, 4),
                month = idNumber.substr(10, 2);

            if (year >= 1961 && year <= 1996 && month >= 1 && month <= 12) {
                form.find('select[name="year"]').val(year);
                form.find('select[name="month"]').val(month);
            }
        }
    },
    render: function() {
        if (isDue) {
            return (
                <div style={{ textAlign: 'center', marginTop: '60%', fontSize: '32pt', color: 'gray' }}>
                    报名已截止
                </div>
            );
        }

        return (
            <div ref="entryForm" className="entryForm">
                <div className="entryForm-item">
                    <h1 className="entryForm-title">创业不孤单·牵手过新年 - 2016武侯区单身联谊报名表</h1>
                </div>
                <div className="entryForm-item">
                    <div className="entryForm-name">姓名<span className="mandatory">*</span></div>
                    <div className="entryForm-wrap">
                        <input type="text" className="entryForm-input" name="name" placeholder="真实姓名"/>
                    </div>
                </div>

                <div className="entryForm-item">
                    <div className="entryForm-name">性别<span className="mandatory">*</span></div>
                    <div className="entryForm-wrap sex-wrap">
                        <table className="sex-item">
                            <tbody>
                            <tr>
                                <td><input type="radio" value="1" id="gender_male" name="gender" />
                                    <label htmlFor="gender_male">男</label>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="radio" defaultChecked={true} value="2" id="gender_female" name="gender" />
                                    <label htmlFor="gender_female">女</label>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="entryForm-item">
                    <div className="entryForm-name">身份证号码<span className="mandatory">*</span></div>
                    <div className="entryForm-wrap">
                        <input type="text" className="entryForm-input" name="id_number" onChange={this.onIdNumberChanged} />
                    </div>
                </div>

                <div className="entryForm-item">
                    <div className="entryForm-name">出生年月<span className="mandatory">*</span></div>
                    <div className="entryForm-wrap">
                        <label className="select">
                            <select className="select" name="year" defaultValue="1996">
                                <option value="1961">1961</option>
                                <option value="1962">1962</option>
                                <option value="1963">1963</option>
                                <option value="1964">1964</option>
                                <option value="1965">1965</option>
                                <option value="1966">1966</option>
                                <option value="1967">1967</option>
                                <option value="1968">1968</option>
                                <option value="1969">1969</option>
                                <option value="1970">1970</option>
                                <option value="1971">1971</option>
                                <option value="1972">1972</option>
                                <option value="1973">1973</option>
                                <option value="1974">1974</option>
                                <option value="1975">1975</option>
                                <option value="1976">1976</option>
                                <option value="1977">1977</option>
                                <option value="1978">1978</option>
                                <option value="1979">1979</option>
                                <option value="1980">1980</option>
                                <option value="1981">1981</option>
                                <option value="1982">1982</option>
                                <option value="1983">1983</option>
                                <option value="1984">1984</option>
                                <option value="1985">1985</option>
                                <option value="1986">1986</option>
                                <option value="1987">1987</option>
                                <option value="1988">1988</option>
                                <option value="1989">1989</option>
                                <option value="1990">1990</option>
                                <option value="1991">1991</option>
                                <option value="1992">1992</option>
                                <option value="1993">1993</option>
                                <option value="1994">1994</option>
                                <option value="1995">1995</option>
                                <option value="1996">1996</option>
                            </select>
                        </label>&nbsp;年&nbsp;&nbsp;
                        <label className="select">
                            <select className="select" name="month">
                                <option value="01">01</option>
                                <option value="02">02</option>
                                <option value="03">03</option>
                                <option value="04">04</option>
                                <option value="05">05</option>
                                <option value="06">06</option>
                                <option value="07">07</option>
                                <option value="08">08</option>
                                <option value="09">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </label>&nbsp;月
                    </div>
                </div>



                <div className="entryForm-item">
                    <label className="entryForm-name">身高(cm)<span className="mandatory">*</span></label>
                    <div className="entryForm-wrap">
                        <input type="tel" className="entryForm-input" name="height" />
                    </div>
                </div>

                <div className="entryForm-item">
                    <label className="entryForm-name">毕业院校<span className="mandatory">*</span></label>
                    <div className="entryForm-wrap">
                        <input type="text" className="entryForm-input" name="graduate_university" />
                    </div>
                </div>

                <div className="entryForm-item">
                    <div className="entryForm-name">学历<span className="mandatory">*</span></div>
                    <div className="entryForm-wrap sex-wrap">
                        <table className="sex-item">
                            <tbody>
                            <tr>
                                <td><input type="radio" value="0" id="degree_1" name="degree" />
                                    <label htmlFor="degree_1">大专以下</label>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="radio" value="1" id="degree_2" name="degree" />
                                    <label htmlFor="degree_2">大专</label>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="radio" value="2" id="degree_3" name="degree" />
                                    <label htmlFor="degree_3">本科</label>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="radio" defaultChecked={true} value="3" id="degree_4" name="degree" />
                                    <label htmlFor="degree_4">本科以上</label>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="entryForm-item">
                    <label className="entryForm-name">年薪（万元）<span className="mandatory">*</span></label>
                    <div className="entryForm-wrap">
                        <input type="tel" className="entryForm-input" name="yearly_salary" />
                    </div>
                </div>

                <div className="entryForm-item">
                    <label className="entryForm-name">工作单位<span className="mandatory">*</span></label>
                    <div className="entryForm-wrap">
                        <input type="text" className="entryForm-input" name="work_unit" />
                    </div>
                </div>

                <div className="entryForm-item">
                    <label className="entryForm-name">手机号码<span className="mandatory">*</span></label>
                    <div className="entryForm-wrap">
                        <input type="tel" className="entryForm-input" name="mobile" placeholder="短信通知参与活动"/>
                    </div>
                </div>

                <div className="entryForm-item">
                    <label className="entryForm-name">个人才艺</label>
                    <div className="entryForm-wrap">
                        <textarea className="entryForm-textarea" name="talent"></textarea>
                    </div>
                </div>

                <div className="entryForm-item">
                    <label className="entryForm-name">择偶要求</label>
                    <div className="entryForm-wrap">
                        <textarea type="text" className="entryForm-textarea"
                                  name="mate_choice" placeholder="身高、年龄、学历······"></textarea>
                    </div>
                </div>

                <div className="entryForm-item">
                    <label className="entryForm-name">个人照片(最多5张)<span className="mandatory">*</span></label>
                    <div className="entryForm-wrap">
                        {this.state.user.images.map(function(image, index){
                            return <div key={"image" + index} className="photo-wrap mb10"><img src={image + "@150h_1pr_2o.jpg"} alt="" /></div>
                        })}
                        <div className="clearfix">
                            <div className="file-con">
                                <span className="file-text">+</span>
                                <input className="file-input" type="file" name="image" />

                                <div className="uploading-div">
                                    <span className="uploading-icon"></span>
                                    <span className="uploading-text">上传中...</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div className="entryForm-item">
                    <p style={{ margin: '20px 0 4px 0', fontWeight: 'bold' }}>重要提示：</p>
                    <ol style={{ margin: 0, paddingLeft: '24px'}}>
                        <li>为确保本次活动的安全和顺利进行，请如实填写以上信息，若有问题本人承担一切后果。</li>
                        <li>由组委会安排活动之外的个人行为由本人负责。</li>
                        <li>现场活动以收到组委会发送的邀请短信确认为准。</li>
                        <li>为了保证本次活动的品质和私密性，请勿携带亲友团参加活动。</li>
                    </ol>
                </div>
                <div className="entryForm-item tc">
                    <a href="javascript:;" onClick={this.submitHandler} id="submit-info" className="v-btn v-btn-yellow entryForm-btn">提交</a>
                </div>
            </div>
        );
    }
});

module.exports = EntryPanel;