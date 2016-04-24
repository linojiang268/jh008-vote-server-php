/**
 * 消息通知基础模块
 * by pheart
 */

$(function(){

    var Class = K.Class,
        BaseView = K.util.BaseView,
        PageManager = K.util.pageManager,
        Observe = K.Observe,
        server = K.server,
        DialogUi = K.dialogUi,
        NoticeWay = K.NoticeWay;

    var NOGROUPNAME = "未分组";

    // 用户项
    var User = Class.create(BaseView, {
        initialize: function(data) {
            this.data = data;
            this.render();
            this.setEvents();
        },
        events: {
            'click': 'select'
        },
        eventsHandler: {
            select: function(e) {
                this.trigger('_select', this.data);
                e.stopPropagation();
            }
        },
        render: function() {
            if (!this.data) return false;
            this.El = $('<li class="member-item"><a href="javascript:;" class="member-item-link">'+ this.data.name +'</a></li>');
        }
    })

    Observe.make(User.prototype);

    // 用户组项
    var Group = Class.create(BaseView, {
        initialize: function(data) {
            this.data = data;
            this.render();
            this.setEvents();
        },
        downIcon: '<i class="togicon icon iconfont"></i>',
        rightIcon: '<i class="togicon icon iconfont"></i>',
        events: {
            'click': 'toggle',
            'click .addicon': 'select'
        },
        eventsHandler: {
            toggle: function(e) {
                var _this = this,
                    memberNavEl = _this.El.find('.member-nav');
                if (memberNavEl.css('display') == 'none') {
                    _this.El.find('.togicon').replaceWith(_this.downIcon);
                } else {
                    _this.El.find('.togicon').replaceWith(_this.rightIcon);
                }
                memberNavEl.toggle();
            },
            select: function(e) {
                this.trigger('_select', this.data);
                e.stopPropagation();
            }
        },
        render: function() {
            var name = this.data.name;
            var groupName = name == '' ? '未分组' : name;
            this.El = $(    '<li class="nav-item">' + 
                                // '<a class="item-link" href="javascript:;"><i class="togicon icon iconfont"></i>'+ groupName +'<i class="addicon icon iconfont"></i></a>' +
                                '<ul class="member-nav"></ul>' +
                            '</li>');
        },
        append: function(user) {
            this.El.find('.member-nav').append(user.El);
        }
    })
    
    Observe.make(Group.prototype);
    
    // 用户列表管理项
    var UserList = Class.create({
        initialize: function(users /*groups*/) {
            this.groupList = {};
            /*this.groups = groups;
            this.groups.push('');*/
            this.userList = [];
            this.users = users;
            this.noGroupName = NOGROUPNAME;
            this.El = $('<ul class="group-nav"></ul>');
            this.render();
        },
        render: function() {
            var _this = this;
            //_this.renderGroup();
            if (this.users.length) {
                $.each(this.users, function(index, userData) {
                    //var groupName = (userData.group && userData.group.name) || _this.noGroupName,
                    var groupName = _this.noGroupName,
                        group;
                    if (!_this._hasGroup(groupName)) {
                        group = _this.createGroup(groupName);
                    } else {
                        group = _this.groupList[groupName];
                    }
                    var user = _this.createUser(userData);
                    group.append(user);
                })
            }
        },
        _hasGroup: function(name) {
            if (this.groupList[name]) 
                return true;
            return false;
        },
        createGroup: function(groupName) {
            var _this = this;
            var group = new Group({name: groupName});
            group.on('_select', function(groupData) {
                _this.trigger('_select', {type: 'group', data: groupData});
            })
            _this.El.append(group.El);
            _this.groupList[groupName] = group;
            return group;
        },
        createUser: function(userData) {
            var user = new User(userData),
                _this = this;
            user.on('_select', function(userData){
                _this.trigger('_select', {type: 'user', data: userData});
            })
            this.userList.push(user);
            return user;
        },
        selectAll: function() {
            this.trigger('_select', {type: 'all'});
        }
    })

    Observe.make(UserList.prototype);

    // 通知管理
    var SelUserManager = function(container) {
        var userList,
            users = [],
            to_all = false,
            searchList = [],
            selectUserCache = {},
            selNumTextEl = container.find('#selNumText'),
            searchEl = container.find('#search'),
            delSearchEl = container.find('#delSearch'),
            searchNavEl = container.find('#searchNav'),
            delSearch = container.find('#delSearch'),
            hasSelNavEl = container.find('#hasSelNav');

        hasSelNavEl.on('click', '.delicon', function(e) {
            var target = $(e.target).parent(),
                id = target.attr('id');
            target.remove();
            removeUser(id);
            to_all = false;
        })

        searchEl.keyup(function(e){
            var target = $(e.target),
                value = target.val();
            if (value) {
                clearSearchUser();
                var reg = new RegExp('^' + value + '\\s*');
                pageManager.show('searchList');
                $.each(users, function(i, user) {
                    if (reg.test(user.name)) {
                        searchList.push(user);
                        renderSearchUser(user);
                    }
                })
            }
        })

        searchNavEl.on('click', 'a', function(e) {
            var target = $(e.target),
                id = target.attr('id');
            for (var i = 0; i < searchList.length; i++) {
                if (searchList[i].id == id) {
                    selectUser(searchList[i]);
                }
            }
        })

        delSearch.click(function(){
            searchEl.val('');
            clearSearchUser();
            pageManager.show('groupList');
        })

        container.find('.checkbox-wrap').click(function(){
            var selAll = container.find('#selAll');
            if (!selAll[0].checked) {
                userList.selectAll();
            } else {
                removeAll();
            }
        })

        function clearSearchUser() {
            searchList = [];
            searchNavEl.html('');
        }

        function renderSearchUser(userData) {
            var el = '<li class="member-item">' +
                        '<a href="javascript:;" class="member-item-link" id="'+ userData.id +'">'+ userData.name +'</a>' +
                     '</li>';
            searchNavEl.append(el);
        }

        function refreshSelectNumsTip() {
            var selNums = 0;
            for (var key in selectUserCache) {
                selNums ++;
            }
            selNumTextEl.text(selNums + '/' + users.length);
        }

        function removeUser(userId) {
            if (selectUserCache[userId]) {
                delete selectUserCache[userId];
            }
            refreshSelectNumsTip();
        }

        function renderUser(userData) {
            var el = '<li class="member-item">' +
                        '<a href="javascript:;" class="member-item-link" id="'+ userData.id +'">'+ userData.name +'<i class="delicon icon iconfont"></i></a>' +
                     '</li>';
            hasSelNavEl.append(el);
            refreshSelectNumsTip();
        }

        function removeAll() {
            selectUserCache = {};
            hasSelNavEl.html('');
            refreshSelectNumsTip();
            to_all = false;
        }

        function filter(userData) {
            if (!selectUserCache[userData.id]) {
                selectUserCache[userData.id] = userData;
                return true;
            } else {
                return false;
            }
        }

        function selectUser(userData) {
            if (filter(userData)) {
                renderUser(userData);
            }
        }

        function selectGroup(groupData) {
            $.each(users, function(index, user) {
                if (groupData.name == NOGROUPNAME && !user.group) {
                    selectUser(user);
                    return true;
                }
                if (user.group && user.group.name == groupData.name) {
                    selectUser(user);
                }
            })
        }

        function selectAll() {
            $.each(users, function(index, user) {
                selectUser(user);
            });
            to_all = true;
        }

        function _getSelectMobiles() {
            var result = [];
            for (var key in selectUserCache) {
                result.push(selectUserCache[key].mobile);
            }
            return result;
        }

        function getMembers(deferred) {
            server.listMembers({
                page: 1,
                size: 10000
            }, function(resp) {
                if (resp.code == 0) {
                    deferred.resolve(resp);
                } else {    
                    DialogUi.alert(resp.message || '获取成员出错了！');
                }
            })
        }

        function _getRequests() {
            var result = {};
            if (!container.find('#noticeMembersCon').length) {
                result.to_all = 1;
            } else {
                if (to_all) {
                    result.to_all = to_all;
                } else {
                    result.phones = _getSelectMobiles();
                }                
            }
            return result;
        }

        function _init(datas) {
            var deferred = $.Deferred();
            users = datas;
            userList = new UserList(users);
            userList.on('_select', function(result) {
                if (!result) return;
                if (result.type == 'user') {
                    selectUser(result.data);
                } else if (result.type == 'group') {
                    selectGroup(result.data);
                } else if (result.type = 'all') {
                    selectAll();
                }
            });

            pageManager.render('groupList', userList.El);               
        }

        return {
            init: _init,
            getRequests: _getRequests
        }
    };

    /**
     * 消息通知弹出框
     * @params {Object} options
            - El   容器
            - status    状态
            - noticeOriginal  通知框内默认的内容
            - datas 数据
            - sendHandler
     *
     */
    var Notice = function(options) {
        var defaultOptions = {
            El: '',
            status: 'push',
            noticeOriginal: '',
            datas: '',
            sendHandler: ''
        };  

        this.options = $.extend(defaultOptions, options);
        this.El      = this.options.El;
        this.datas   = this.options.datas;
        this.initialize();
    }

    Notice.prototype = {
        constructor: Notice,
        initialize: function() {
            var options = this.options;
            if (!this.El) return false;
            var El    = this.El,
                _this = this;

            // 用户列表面板的子容器管理
            var groupNavConEl = this.El.find('#groupNavCon');
            pageManager = PageManager();
            pageManager.add({name: 'groupList', el: this.El.find('#groupList'), parent: groupNavConEl});
            pageManager.add({name: 'searchList', el: this.El.find('#searchList'), parent: groupNavConEl});

            this.pushWayLimitTextarea = this.El.find('#noticeTextarea');
            this.smsWayLimitTextarea  = this.El.find('#msgTextarea');

            if (options.noticeOriginal) {
                this.pushWayLimitTextarea.val(options.noticeOriginal);
                this.smsWayLimitTextarea.val(options.noticeOriginal);
                this.pushWayLimitTextarea.trigger('keyup');
                this.smsWayLimitTextarea.trigger('keyup');                
            }

            // notice way switch
            lc.SelectManager.on(El.find('#noticeWay'), function(value) {
                var data = _this.getData();
                if (value == 1) {
                    _this.setStatus('push');
                    _this.pushWayLimitTextarea.val(data.content).trigger('keyup');
                    _this.smsWayLimitTextarea.val('');
                } else if (value == 2) {
                    _this.setStatus('sms');
                    _this.pushWayLimitTextarea.val('');
                    _this.smsWayLimitTextarea.val(data.content).trigger('keyup');
                }
            });

            // 初始化SelUserManager
            this.selUserManager = SelUserManager(this.El);
            this.selUserManager.init(this.datas);

            this.setSendHandler();
            this.render();
        },
        // 发送按钮事件
        setSendHandler: function() {
            var _this = this,
                noticeWay = this.noticeWay;

            this.El.find('#sendBtn').click(function() {
                var requests    = _this.selUserManager.getRequests();
                var result      = _this.getData();
                var sendHandler = _this.options.sendHandler;

                if (sendHandler && $.type(sendHandler) == 'function') {
                    sendHandler.call(_this, $.extend(requests, result), _this);
                }
            });
        },
        setStatus: function(status) {
            this.options.status = status;
            this.render();
        },
        render: function() {
            var container = this.El,
                status    = this.options.status;
            if (status == 'push') {
                container.find('#pushWayLimit').show();
                container.find('#smsWayLimit').hide();
            } else if (status == 'sms') {
                container.find('#pushWayLimit').hide();
                container.find('#smsWayLimit').show();
            }
        },
        getData: function() {
            var result = {},
                status = this.options.status,
                container = this.El;

            if (status == 'push') {
                result.content = this.pushWayLimitTextarea.val();
            } else if (status == 'sms') {
                result.content = this.smsWayLimitTextarea.val();
            }

            result.status = status;
            return result;
        },
        clear: function() {
            var status = this.options.status,
                container = this.El;

            if (status == 'push') {
                container.find('#pushWayLimit').find('textarea').val('');
            } else if (status == 'sms') {
                container.find('#smsWayLimit').find('textarea').val('');
            }
        }
    }

    /**
     * noticedialog 通知消息弹出框  比较通用
     * @options {Object} 参数与Notice相同
            - template   模板
            - status    状态
            - noticeOriginal  通知框内默认的内容
            - datas 数据
            - sendHandler
            - sendAjaxMethod 
            - title 弹窗标题
     *
     */
    var NoticeDialog = function(options) {
        var _this = this;
        var defaultOptions = {
            template: '',
            status: 'push',
            noticeOriginal: '',
            datas: '',
            sendHandler: function(requests, notice) {
                var flag = _this.flag;

                if (flag) {
                    return _this.tip('正在请求中...');
                }

                _this.flag = true;
                if (!requests.status) {
                    return _this.tip('请选择发送通知方式');
                }

                if (!requests.content) {
                    return _this.tip('通知内容不能为空');
                }

                if (!requests.to_all && !requests.phones.length) {
                    return _this.tip('请选择需要发送的人员');
                }

                requests.to_all ?  requests.to_all = 1 : requests.to_all = 0;  
                requests.send_way = requests.status;
                requests._token = $('input[name="_token"]').val();
                _this.tip('消息发送中...');
                _this.options.sendAjaxMethod.call(_this, requests, function() {
                    _this.flag = false;
                }, _this.tip);
            },
            sendAjaxMethod: function(requests, next, tip){
                var _this = this;
                server.sendNotice(requests, function(resp) {
                    next();
                    if (resp.code == 0) {
                        tip('消息发送成功');
                        _this.notice.clear();
                    } else {
                        tip(resp.message || '发送失败!');
                    }
                });
            },
            title: ''
        }
        this.options = $.extend(defaultOptions, options);
        this.flag = false;
        this.initialize();
    }

    NoticeDialog.prototype = {
        constructor: NoticeDialog,
        initialize: function() {
            var _this = this;
            this.dialog = DialogUi.open({
                title: this.options.title || '发送通知',
                content: $('#' + _this.options.template).html(),
                area: ['730px'],
                success: function(layero, index) {
                    _this.El = layero.find('.notice-dialog');
                    layero.find('#noticeWay').lc_radioSel();
                    layero.find('#pushWayLimit').lc_limitText();
                    layero.find('#smsWayLimit').lc_limitText();
                    layero.find('.sel-all').lc_checkboxSel();

                    _this.notice = new Notice({
                        El: layero.find('.notice-dialog'),
                        status: _this.options.status,
                        noticeOriginal: _this.options.noticeOriginal,
                        datas: _this.options.datas,
                        sendHandler: _this.options.sendHandler
                    });
                },
                btn: false
            });
        },
        tip: (function(){
            var timer;
            return function (text) {
            var notice_dialog_error = this.El.find('#noticeSelError');    
                if (timer) {
                    clearInterval(timer);
                }
                notice_dialog_error.text(text).show();
                timer = setInterval(function(){
                    clearInterval(timer);
                    notice_dialog_error.text('').hide();
                }, 2000);
            }
        })()
    }

    K.Notice       = Notice;
    K.NoticeDialog = NoticeDialog;
})