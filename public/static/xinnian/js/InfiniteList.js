/**
 * Infinite list view
 */
var InfiniteList = React.createClass({
    getInitialState: function() {
        return {
            items: [],  // concatenated items
            page: this.props.startingPage - 1, // the current page in display
            hasMore: true, // has more items to load?

            loading: false, // whether we're in process of loading data
            error: null, // error message
        };
    },

    propTypes: {
        startingPage: React.PropTypes.number,
        fetch: React.PropTypes.func.isRequired,
        renderItem: React.PropTypes.func.isRequired,
        renderNoItem: React.PropTypes.func.isRequired,
        fetchOnComponentDidMount: React.PropTypes.bool,
    },

    getDefaultProps: function() {
        return {
            startingPage: 1,
            renderNoItem: function () {
                return (
                    <div style={styles.no_item}> 无数据 </div>
                );
            },
            fetchOnComponentDidMount: false
        };
    },

    fetch: function() { // fetch next page of items
        // if there's no more data to load or we're in process of loading
        if (!this.state.hasMore || this.state.loading) {
            return; // do nothing
        }

        this.setState({ loading: true, error: null }); // claim that we're in process of loading

        var self = this;
        this.props.fetch(this.state.page + 1, function (error, result) {
            if (error != null) {
                return self.setState({ loading: false, error: error, hasMore: false });
            }

            if (result != null) {
                self.setState({
                    items: self.state.items.concat(result.items),
                    page: self.state.page + 1,
                    hasMore: result.hasMore,
                    loading: false,
                });
            } else {
                // either error or result is null, assume that there is not actually loading at all
                self.setState({ loading: false });
            }
        });
    },

    componentDidMount: function() {
        // install scroll event listener to detect whether the bottom is hit or not
        var scrollingComponent = this.findScrollingComponent();
        $(scrollingComponent).on('scroll', this.onScroll);

        this.props.fetchOnComponentDidMount && self.fetch();
    },

    componentWillUnmount: function() {
        var scrollingComponent = this.findScrollingComponent();
        $(scrollingComponent).off('scroll', this.onScroll);
    },

    onScroll: function () {
        var scrollingComponent = this.findScrollingComponent(),
            self = this;
        var documentEl = $(scrollingComponent), scrollTop = documentEl.scrollTop();
        if (scrollTop + documentEl.innerHeight() >= documentEl.children().height()) {
            // TODO: tell the scroll up and scroll down
            self.fetch();
        }
    },

    findScrollingComponent: function () {
        return this.props.scrollingComponent || this.refs.list.getDOMNode();
    },

    render: function() {
        var itemsComponent = null,
            loadingMask = null,
            noItemComponent = null,
            errorComponent = null,
            self = this;

        if (this.state.items.length > 0) {
            itemsComponent = this.state.items.map(function (item) {
                return self.props.renderItem(item);
            });
        }

        if (this.state.error) {
            errorComponent =  <span className="load-text">{ this.state.error }</span>;
        } else {
            if (this.state.loading) {
                loadingMask = (
                    <div>
                        <span className="load-img"></span>
                        <span className="load-text">正在加载...</span>
                    </div>
                );
            } else {
                if (this.state.items.length == 0 && !this.state.hasMore) {
                    noItemComponent = this.props.renderNoItem();
                }
            }
        }

        return (
            <div ref="list" style={styles.container}>
                <ul className="votePhotos clearfix">
                    { itemsComponent }
                </ul>
                <div ref="loadingMask" className="load-div">
                    { loadingMask }
                    { noItemComponent }
                    { errorComponent }
                </div>
            </div>
        );
    }
});


var styles = {
    container: {
        //minHeight: '32px',
    },
    no_item: {
        paddingTop: '24px',
        paddingBottom: '12px',
        textAlign: 'center',
        fontSize: '12px',
    }
};

module.exports = InfiniteList;