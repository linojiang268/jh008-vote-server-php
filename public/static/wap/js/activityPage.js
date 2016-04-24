$(function(){

	//活动地点坐标
	var location_points = $('#location').val().split(','),
		//活动地址
		address = $('.ac-address').text(),
		//活动城市
		city = $('#city').val() || '成都';
		//路线坐标点集合
		// roadmap = $('#roadmap').text().trim().replace(/\s/g,"");//获取坐标后去掉中间和两边的空格

		// console.log(roadmap.replace(/\s/g,""));

	var map = new BMap.Map("map");            // 创建Map实例
	var point = new BMap.Point(location_points[1],location_points[0]);    // 创建点坐标
	
	if (location_points) {
		map.centerAndZoom(point,14);
	}else {
		map.centerAndZoom(city,14);
	}	
	
	// 初始化地图,设置中心点坐标和地图级别，标注目标点
	map.addControl(new BMap.ZoomControl()); 
	var marker1 = new BMap.Marker(point);
	var infowindow = new BMap.InfoWindow(address,{
		width : 200,
		height : 60,
		enableAutoPan : true
	});
	map.addOverlay(marker1);
	marker1.addEventListener('click',function(){
		map.openInfoWindow(infowindow,point);
	});

	//添加折线(经过确认，暂时不添加路线)
	// var points = getRoadmapPoints(roadmap);
	// var polyline = new BMap.Polyline(getMapPoints(points),{strokeColor:"blue", strokeWeight:6, strokeOpacity:0.5});
	// map.addOverlay(polyline);
	// 
	$('#location-btn').on('click',function(){
		map.setCenter(point); 
	});
});

function getRoadmapPoints(str){
	//str : xxx-xxx,xxx-xxx,xxx-xxx,
	var arr = str.split(','),
		arr2 = [];
	for (var i = 0; i < arr.length; i++) {
		if (arr[i]) {
			arr2.push(arr[i]);
		}
	}
	return arr2;
}

function getMapPoints(points) {
	//points : [a-b,c-d,e-f,...]
	var arr = [];
	for (var i = 0 , len = points.length ; i < len; i++ ) {
		var p = points[i].split('-');
		var mapPoint = new BMap.Point(p[0],p[1]);//p[0]为经度，p[1]为纬度
		arr.push(mapPoint);
	}
	console.log(arr);
	return arr;
}