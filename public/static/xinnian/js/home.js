var Search = require('./search'),
    InfiniteList = require('./InfiniteList');

/* 首页banner */
var HomeBanner = React.createClass({
    getInitialState: function () {
        return {
            closed: false,
        };
    },

    render: function() {
        //var enrollComponent = null;
        //if (this.state.closed) {
        //    enrollComponent = (
        //        <a className="go-entry-btn go-entry-btn-end clearfix" href="javascript:;">报名结束</a>
        //    );
        //} else {
        //    enrollComponent = (
        //        <a className="go-entry-btn clearfix" href="#entry">立即报名</a>
        //    );
        //}

        return (
            <div>
                <div className="banner">
                    <img src={prefix + "/static/xinnian/images/banner.jpg"} className="res-img" alt="banner" />
                </div>
                <Search/>
            </div>
        );
    }
});

//var cache = {};

/* 点赞照片 */
var VotePhoto = React.createClass({
    getInitialState: function() {
        return {
            vote_count: 0,
        }
    },
    componentDidMount: function() {
        /*var imgCon = $(this.refs.imgCon.getDOMNode());
        if (!cache.width) {
            cache.width = imgCon.width();
        }
        imgCon.height(cache.width);*/
        var vote_count = this.props.data.vote_count || 0;
        this.setState({vote_count: vote_count});
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
        params.user = this.props.data.id;
        K.server.attendantVote(params, function(resp) {
            var text = '',
                title = '提示';
            if (resp.code == 0) {
                alert('点赞成功');
                _this.setState({vote_count: _this.state.vote_count + 1});
                return;
            } else if (resp.code == 1) {
                text = '<p class="tl">对不起，今天您已投满5票，请明天再来^_^</p>';

            } else if (resp.code == 2) {
                text = '<p class="tl">对不起，今天您已经投过票了，请明天再来^_^</p>';
            } else {
                text = resp.message || '网络不给力，请重试';
                title = '点赞失败';
            }

            K.aModal({
                title: title,
                content: text,
                okCallback: function() {
                }

            });   
        });
    },
    componentWillReceiveProps: function(nextProps) {
        this.setState({vote_count: nextProps.data.vote_count});
    },
    render: function() {
        var data = this.props.data,
            id = data.id;

        var voteComponent = isDue ? null : (
            <div className="tc">
                <span href="javascrpt:;" onClick={this.doVote} className="v-btn v-btn-red vote-btn">给TA点赞</span>
            </div>
        );

        return (                                                                                                                                                                                                               
            <li className="vote-photo" ref="voteItem">
                <Link className="vote-photo-link" to="detail" data-id={id} params={{id: this.props.data.id}}>
                    <div ref="imgCon" id="imgCon" className="vote-photo-item-w"><img className="photo" src={this.props.data.cover_url} alt="" /></div>
                    <div className="vote-photo-content">
                        <span className="name">{this.props.data.name}</span>
                        <div className="clearfix mt5">
                            <span className="number">编号：{this.props.data.order_id}</span>
                            <span className="ticket">
                                <span className="ticket-num">{this.state.vote_count}</span>
                            </span>
                        </div>
                        { voteComponent }
                    </div>
                </Link>
            </li>
        );
    }
});

/* 点赞照片墙 */
var VotePhotos = React.createClass({
    getInitialState: function () {
        return {
            display: this.props.display,
        };
    },

    propTypes: {
        display: React.PropTypes.bool,
    },

    getDefaultProps: function() {
        return {
            display: false
        };
    },

    componentWillReceiveProps: function(nextProps){
        this.setState(nextProps);

        if (nextProps.display) {
            setTimeout(function () {
                this.refs.list.fetch();
            }.bind(this), 300);
        }
    },

    fetch: function (page, callback) {
        if (!this._shouldFetch()) return callback(null, null);
        K.server.attendantList({ page: page }, function(resp) {
            if (resp.code == 0) {
                callback(null, {
                    items: resp.attendants,
                    hasMore: resp.pages > page
                });
            } else {
                callback(resp.message || '获取数据出错了,请重新打开页面试试');
            }
        });
    },

    renderItem: function (attendant) {
        return (
            <VotePhoto data={attendant} key={"all" + attendant.id} />
        );
    },

    renderNoItem: function () {
        return (
            <span className="load-text"> 暂无报名选手 </span>
        );
    },

    _shouldFetch: function () {
        return route_name == 'home' && this.state.display;
    },

    render: function () {
        return (
            <div style={{ display: this.state.display ? 'block' : 'none' }}>
                <InfiniteList ref="list"
                              fetch={this.fetch}
                              renderItem={this.renderItem}
                              scrollingComponent={"div#app"}
                              renderNoItem={this.renderNoItem} />
            </div>
        );
    },
});


/* TopPhotos */
var TopPhotos = React.createClass({
    getInitialState: function () {
        return {
            display: this.props.display,
        };
    },

    propTypes: {
        display: React.PropTypes.bool,
    },

    getDefaultProps: function() {
        return {
            display: false
        };
    },

    componentWillReceiveProps: function(nextProps){
        this.setState(nextProps);

        if (nextProps.display) {
            setTimeout(function () {
                this.refs.list.fetch();
            }.bind(this), 300);
        }
    },

    fetch: function (page, callback) {
        if (!this._shouldFetch()) return callback(null, null);
        K.server.attendantSortList({ page: page }, function(resp) {
            if (resp.code == 0) {
                callback(null, {
                    items: resp.attendants,
                    hasMore: resp.pages > page
                });
            } else {
                callback(resp.message || '获取数据出错了,请重新打开页面试试');
            }
        });
    },

    renderItem: function (attendant) {
        return (
            <VotePhoto data={attendant} key={"top" + attendant.id} />
        );
    },

    renderNoItem: function () {
        return (
            <span className="load-text"> 尚未评选 </span>
        );
    },

    _shouldFetch: function () {
        return route_name == 'home' && this.state.display;
    },

    render: function () {
        return (
            <div style={{ display: this.state.display ? 'block' : 'none' }}>
                <InfiniteList ref="list" fetch={this.fetch}
                              renderItem={this.renderItem}
                              scrollingComponent={"div#app"}
                              renderNoItem={this.renderNoItem} />
            </div>
        );
    }
});

/* home panel */
var HomePanel = React.createClass({
    getInitialState: function () {
        return {
            show: 'all'
        };
    },

    listHandler: function () {
        this._toggleButtonOnState('defaultBtn');
        this.setState({ show: 'all' });
    },

    sortHandler: function () {
        this._toggleButtonOnState('sortBtn');
        this.setState({ show: 'top' });
    },

    _toggleButtonOnState: function (button) {
        $('div.sort-w button').removeClass('on');
        $(this.refs[button].getDOMNode()).addClass('on');
    },

    render: function() {
        return (
            <div className="homePanel" >
                <HomeBanner />
                <div id="actionPanel" className="sort-w">
                    <button ref="defaultBtn" className="default-btn on" onClick={this.listHandler}>选手列表</button>
                    <button ref="sortBtn" className="sort100-btn" onClick={this.sortHandler}>点击查看 Top 100</button>
                </div>
                <VotePhotos display={this.state.show == 'all'} />
                <TopPhotos display={this.state.show == 'top'} />
            </div>
        );
    }
});

module.exports = HomePanel;