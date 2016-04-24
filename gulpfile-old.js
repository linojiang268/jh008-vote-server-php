var gulp = require('gulp'),
	uglifycss = require('gulp-uglifycss'),
	uglifyjs = require('gulp-uglify'),
	concat = require('gulp-concat');

var cssConfig = {
	basepath: '../static/css/',
	uglifyConfig: [
		{
			src: [  'reset.css', 
					'base.css', 
					'iconfont/iconfont.css', 
					'lc_ui.css', 
					'lc_layer.css', 
					'../plugins/ktable/skins/k-table.css',
					'common.css'],
			dest: '../static/min/css/',
			name: 'common.min.css'
		},
		{
			src: 'login.css',
			dest: '../static/min/css/',
			name: 'login.min.css'
		},
		{
			src: 'assn.css',
			dest: '../static/min/css/',
			name: 'assn.min.css'
		},
		
	]
}

var jsConfig = {
	basepath: '../static/js/',
	uglifyConfig: [
		{
			src: [  '../plugins/jquery-1.7.1.min.js', 
					'../plugins/layer/layer.js', 
					'../plugins/jquery.validate.js', 
					'../plugins/ktable/utilHelper.js', 
					'../plugins/ktable/k-paginate.js', 
					'../plugins/ktable/k-table.js' ],
			dest: '../static/min/js/',
			name: 'third.min.js'
		},
		{
			src: [  'lc.js',
					'K.js', 
					'base.js', 
					'util.js', 
					'dialogUi.js', 
					'server.js' ],
			dest: '../static/min/js/',
			name: 'common.min.js'
		}
	]
}

function type(val) {
	return Object.prototype.toString.call(val).slice(8, -1).toLowerCase();
}

gulp.task('uglifyCss', function(){
	var config = cssConfig.uglifyConfig;
	if (config.length) {
		config.forEach(function(value, index){
			var targetSrc = [], srcType;
			if (value.src) {
				srcType = type(value.src);
				if (srcType == 'array') {
					value.src.forEach(function(src){
						targetSrc.push(cssConfig.basepath + src);
					})					
				} else if (srcType == 'string') {
					targetSrc = cssConfig.basepath + value.src;
				}

				if (index + 1 >= config.length ) {
					return  gulp.src(targetSrc)
							// 压缩
							.pipe(uglifycss())
							.pipe(concat(value.name))
							.pipe(gulp.dest(value.dest));
				} else {
					gulp.src(targetSrc)
						// 压缩
						.pipe(uglifycss())
						.pipe(concat(value.name))
						.pipe(gulp.dest(value.dest));
				}
			}
		})
	}
})

gulp.task('uglifyJs', function(){
	var config = jsConfig.uglifyConfig;
	if (config.length) {
		config.forEach(function(value, index){
			var targetSrc = [], srcType;
			if (value.src) {
				srcType = type(value.src);
				if (srcType == 'array') {
					value.src.forEach(function(src){
						targetSrc.push(jsConfig.basepath + src);
					})					
				} else if (srcType == 'string') {
					targetSrc = jsConfig.basepath + value.src;
				}console.log(targetSrc)

				/*if (index + 1 >= config.length) {console.log(111)
					return 	gulp.src(targetSrc)
							// 压缩
							.pipe(uglifyjs())
							.pipe(concat(value.name))
							.pipe(gulp.dest(value.dest));
				} else {console.log(2222)*/
					gulp.src(targetSrc)
						// 压缩
						.pipe(uglifyjs())
						.pipe(concat(value.name))
						.pipe(gulp.dest(value.dest));
				//}
			}
		})
	}
})

gulp.task('default', ['uglifyCss', 'uglifyJs'], function() {
	console.log('has complete!');
})


