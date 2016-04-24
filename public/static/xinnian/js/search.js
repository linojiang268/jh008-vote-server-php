var Search = React.createClass({
	searchHandler: function(){
		var searchCon = $(this.refs.searchSection.getDOMNode()),
			searchInput = searchCon.children('#search-input'),
			VoteLinks = searchCon.parent().siblings().children('ul').find('a');
		var id = $.trim(searchInput.val()),
			flag = 0;
		if ( !id ) {
			K.aModal({content:"请输入编号。"});
			return;
		}
		/*$.each(VoteLinks,function(index,el){
			if ( id == $(el).data('id') ) {
				searchInput.val('');
				flag = 1;
				el.click();
				return false;
			}
		});*/
		K.server.searchAttendant({
            attendant: id
        }, function(resp){
            if (resp.code == 0) {
                var url = window.location.href.split('#')[0];
				window.location.href = url + '#/user/' + resp.id;
            } else {
            	searchInput.val();
                alert(resp.message || '获取用户信息出错了,请重新输入编号搜索'); 
            }
        });
	},
	scrollHandler: function(){
		var offsetY = $('#actionPanel').offset().top;
		if ( offsetY <= 72 ) {
			$(this.refs.searchSection.getDOMNode()).addClass('fixed');
		} else {
			$(this.refs.searchSection.getDOMNode()).removeClass('fixed');
		}
	},
	componentWillUnmount: function() {
    	$('#app').off('scroll', this.scrollHandler);
	},
	componentDidMount: function(){
		$('#app').on('scroll', this.scrollHandler);
	},
	render: function(){
		return(
			<div ref="searchSection" className="search-section">
				<input id="search-input" className="search-input" type="tel" placeholder="请输入选手编号(例如：13)"/>
				<button id="search-btn" className="search-btn" onClick={this.searchHandler}>搜索</button>
			</div>
		);
	}
});

module.exports = Search;