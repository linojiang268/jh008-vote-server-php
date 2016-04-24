$(function(){

    var common = K.ns('common');

    var server = K.server,
        DialogUi = K.dialogUi;
 /*    $(".head-nav").off().on('click', '.logout', function() {
        Server.logout({},function(resp){
            if (resp.code == 0) {
                window.location.href = '/admin';
                return true;
            }else {
                Dialog.alert(resp.message || "退出失败");
                return false;
            }
        });
        return false;
    });*/

    /**
     * list filters
     *
     */
    common.getFilters = function(){
        var result = {};
        var filterCon = $('.filter-bar'),
            search    = $('#search'),
            searchVal = search.val().trim();
        $.each(filterCon, function (index) {
            var filter = filterCon.eq(index);
            var role = filter.attr('data-role');
            if (role) {
                result[role] = filter.find('.filter-select a').attr('data-v');
            }
        });

        if (searchVal) {
            result.keyword = searchVal;
        }
        return result;
    }

    /**
     * get all tag
     *
     */
    common.getTags = function() {
        var tags = null;
        return function(tagDeferred) {
            if (!tags) {
                server.tags({page: 1}, function(resp) {
                    if (resp.code == 0) {
                        tags = resp.tags;
                        tagDeferred.resolve(tags);
                    }
                })
            } else {
                tagDeferred.resolve(tags);
            }
        }
    }();

    /**
     * set tag 
     *
     */
    var SetTags = function(selectTags, alltags) {
        this.selectTags = selectTags;
        this.tags = alltags;
        this.tagList = [];
        this.initialize();
    }

    SetTags.prototype = {
        constructor: SetTags,
        initialize: function() {
            this.El = $('<div class="tag-dia">'+
                    '<div class="tag-dia-top>' +
                        '<h5 class="tip1">已选标签:</h5>' +
                        '<div id="tagTop"></div>' +
                    '</div>' +
                    '<div class="tag-dia-bot>' +
                        '<h5 class="tip1">所有标签:</h5>' +
                        '<div id="tagBot"></div>' +
                    '</div>' +
                +'</div>');

            var tags = this.tags,
                _this = this,
                selectTags = this.selectTags;
            $.each(tags, function(index, tag) {
                var flag = false;
                if (~$.inArray(tag.name, selectTags))
                    flag = true;
                _this.tagList.push(_this.createTag(tag, flag));
            });

            this.render();
        },
        render: function() {
            var selectCon = this.El.find('#tagTop'),
                unSelectCon = this.El.find('#tagBot');
            $.each(this.tagList, function(i, tag) {
                if (tag.select) {
                    selectCon.append(tag.El);
                } else {
                    unSelectCon.append(tag.El);
                }
            })
        },
        createTag: function(tagData, select) {
            return new Tag(tagData, select, this);
        },
        getSelectTags: function() {
            var result = [];
            $.each(this.tagList, function(index, tag) {
                if (tag.select) {
                    result.push(tag.tag.name);
                }
            })
            return result;
        }
    }

    /**
     * tag.
     *
     */
    var Tag = function(tag, select, parent) {
        this.tag = tag;
        this.parent = parent;
        this.select = select || false;
        this.initialize();
    }

    Tag.prototype = {
        constructor: Tag,
        initialize: function() {
            this.El = $('<a href="javascript:;" class="tag-button button button-m button-b-orange"></a>');
            this.render();
            this.setEvents();
        },
        setSelect: function(select) {
            if (select != this.select) {
                this.select = select;
                this.render();
                this.parent.render();
            }
        },
        render: function() {
            var tag = this.tag;
            if (this.select) {
                this.El.html(tag.name + '<i class="icon iconfont"></i>');
            } else {
                this.El.html(tag.name);
            }
        },
        setEvents: function() {
            var _this = this;
            this.El.on('click', function(e) {
                var target = $(e.target);
                if (target.hasClass('iconfont')) {
                    _this.setSelect(false);
                } else {
                    _this.setSelect(true);
                }
                e.stopPropagation();
            })
        }
    }

    common.SetTags = SetTags;

});