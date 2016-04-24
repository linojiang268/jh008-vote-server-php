$(function() {

var MapHelper = K.ns('mapHelper');

/**
 * map helper tools
 * depend on Baidu Map
 * @parms {Object} Map   the instance of  BMap.Map Class
 *
 */
MapHelper.DrawLines = function(map) {
    this.map = map;
    this.initalize();
}

MapHelper.DrawLines.prototype = {
    constructor: MapHelper.DrawLines,
    initalize: function() {
        this.markers = [];  //所有点的集合
        this.polyline = null; //绘制的路径
        this.id = 1; //marker的自增编号
        this.points = [];  //markers的点列表
        this.start_label = new BMap.Label('起点', {offset: new BMap.Size(-5, -20)}); //起点标志
        this.end_label = new BMap.Label('终点', {offset: new BMap.Size(-5, -20)}); //终点标志
        this.start = null;  //起点
        this.end = null; //终点
    },

    // 添加一个marker
    addMarker: function(point) {
        var marker = new BMap.Marker(point),
            _this = this;
        marker.enableDragging();//启用拖拽功能
        this.map.addOverlay(marker);
        this.markers.push(marker);
        this.draw();
        marker.addEventListener('dragging', function () {
            _this.dragMarkHandler();
        });
        marker.addEventListener('dragend', function () {
            _this.dragMarkHandler();
        });
    },

    // 删除最后一个点
    removeMarker: function () {
        if (this.markers.length > 0) {
            var marker = this.markers.pop();
            this.map.removeOverlay(marker);
            this.draw();
        }
    },

    // 拖拽点的时候调用
    dragMarkHandler: function () {
        this.draw();
    },

    // 绘制路径线条
    draw: function() {
        var points = this.getPoints();

        if (this.polyline) {
            this.map.removeOverlay(this.polyline);
        }

        this.polyline = new BMap.Polyline(points);
        this.map.addOverlay(this.polyline);
        this.setIcons();
    },

    // 获取到markers 的点
    getPoints: function () {
        var markers = this.markers;
        this.points = [];
        this.start = markers[0]; //起点
        for (var i in this.markers) {
            if (this.markers[i] && this.markers[i].getPosition) {
                var point = this.markers[i].getPosition();
                this.points.push(point);
            }
            this.end = this.markers[i];
        }
        return this.points;
    },

    //设置起点和终点提示
    setIcons: function () {
        if (this.start && this.start.setLabel) {
            this.start.setLabel(this.start_label);
        }

        if (this.markers.length > 1 && this.end && this.end.setLabel) {
            this.end.setLabel(this.end_label);
        }
    }
}


})