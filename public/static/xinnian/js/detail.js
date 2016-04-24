var degrees = [
    "大专以下", "大专", "本科", "本科以上"
];

var DetailPanel = React.createClass({
    mixins: [StateMixin],
    getInitialState: function() {
        return {
            user: {}
        } 
    },
    componentWillMount: function() {
        var _this = this;
        // ajax 获取 用户数据
        var id = this.getParams().id;
        K.server.attendantDetail({
            attendant: id
        }, function(resp){
            if (resp.code == 0) {
                var data = _this.state.user;

                if ((data && data.id) != resp.id) {
                    resp.gender = resp.gender == 1 ? '男' : '女';
                    resp.graduate_university = resp.graduate_university ? resp.graduate_university : '--';
                    resp.degree = ((resp.degree >= 0 && resp.degree < degrees.length) ? degrees[resp.degree] : degrees[0]);
                    _this.setState({user: resp});
                }
            } else {
                alert(resp.message || '获取用户信息出错了'); 
            }
        });
    },
    doVote: function(e) {
        e.stopPropagation();
        e.preventDefault();
        if (!openId) {
            alert('请使用在微信中打开');
            return;
        }

        var params = {};
        var _this = this;
        params.voter = openId;
        params.type = 2;
        params.user = this.state.user.id;
        K.server.attendantVote(params, function(resp) {
            var text = '',
                title = '提示';
            if (resp.code == 0) {
                alert('点赞成功');
                var userDate = _this.state.user;
                userDate.vote_count += 1;
                _this.setState({user: userDate});
                return;
            } else if (resp.code == 1) {
                text = '<p class="tl">对不起，您今天的点赞配额已用完，请明天再来^_^</p><p class="tl">您可以分享给朋友，邀请他们一起点赞。</p>';

            } else if (resp.code == 2) {
                text = '<p class="tl">对不起，今天您不能点赞了，请明天再来^_^</p>';
            } else {
                text = resp.message || '网络不给力，请重试';
                title = '点赞失败';
            }

            K.aModal({
                title: title,
                content: text,
                okCallback: function() {
                    //if (resp.code == 2) {
                    //    location.hash = 'download';
                    //}
                }
            });   
        });
    },
    render: function() {
        var user = this.state.user;
        var voteComponent = isDue ? null : (
            <a href="javascript:;" onClick={this.doVote} className="v-btn v-btn-red entryForm-btn">给TA点赞</a>
        );

        return (
            <div className="detailPanel">
                <div className="detailPanel-top">
                    <p className="bar">
                        <span className="bar-tip">编号：{this.state.user.order_id}</span>
                    </p>
                    <span className="bar-name">{this.state.user.name}</span>
                </div>
                <div className="detailPanel-rank clearfix">
                    <div className="rank-item rank-item-b">
                        <span className="tip1">人气</span>
                        <span className="tip2">{this.state.user.vote_count}</span>
                    </div>
                    <div className="rank-item">
                        <span className="tip1">当前排名</span>
                        <span className="tip2">{this.state.user.vote_sort ? '第' + this.state.user.vote_sort + '名' : '--'}</span>
                    </div>
                </div>
                <div className="detailPanel-content">
                    <div>
                    {
                        user.images_url ? user.images_url.map(function(photo, index){
                            return  <div className="photo-wrap" key={'image' + index}><img src={photo} /></div>
                        }) : null
                    }
                        <div className="clearfix"></div>
                    </div>
                    <div className="detailPanel-info clearfix">
                        <ul className="clearfix" style={{ margin: 0, padding: 0 }}>
                            <li>性别：{user.gender}</li>
                            <li>出生年月：{user.date_of_birth}</li>
                            <li>身高：{user.height}厘米</li>
                            <li>年薪：{user.yearly_salary}万</li>
                        </ul>
                        <ul className="clearfix" style={{ margin: 0, padding: 0 }}>
                            <li style={{ width: '100%' }}>工作单位：{user.work_unit}</li>
                        </ul>
                        <ul className="clearfix" style={{ margin: 0, padding: 0 }}>
                            <li style={{ width: '100%' }}>毕业院校/学历：{user.graduate_university}/{user.degree}</li>
                        </ul>
                        <ul className="clearfix" style={{ margin: 0, padding: 0, width: '100%' }}>
                            <li style={{ width: '100%' }}>个人才艺：{user.talent ? user.talent: '无'}</li>
                        </ul>
                        <ul className="clearfix" style={{ margin: 0, padding: 0, width: '100%' }}>
                            <li style={{ width: '100%' }}>择偶要求：{user.mate_choice ? user.mate_choice : '无'}</li>
                        </ul>
                    </div>

                    { voteComponent }
                </div>
            </div>
        );
    }
});

module.exports = DetailPanel;