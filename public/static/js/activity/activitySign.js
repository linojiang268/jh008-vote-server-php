$(function() {
	var Server = K.server,
		Util   = K.util,
		Observe = K.Observe,
		Dialog = K.dialogUi,
		MapHelper = K.mapHelper,
		server = K.server;

	var _token = $('input[name="_token"]').val();

	function tip(el, text) {
		return Dialog.tip(el, text);
	}

	function message(text) {
		return Dialog.message(text);
	}	

	function alert(text) {
		return Dialog.alert(text);
	}

	function loading(text) {
		return Dialog.loading(text);
	}

	function makeRoute(name) {
		location.hash = name;
	}

	var tabManager = lc.tabs('#tabs');
	// 活动流程设置页
	tabManager.make('process', function(El, hasInit) {
		makeRoute('process_hash');
		// 流程设置子项目
		var ProcessItem = function(data, status) {
			this.data = $.extend({
				begin_time: '',
				end_time: '',
				plan_text: ''
			}, data || {});
			this.status = status || 'normal';
			this.id = new Date().getTime() + Math.floor(Math.random()*100);
			this.El =  $(' <tr class="process-item mt10"> ' +
                	'<td><input type="text" id="begin_time_'+ this.id +'" name="begin_time" class="form-control btime-input"></td>' +
                    '<td><input type="text" id="end_time_' + this.id +'" nam="end_time" class="form-control etime-input"></td>' +
                    '<td><input type="text" name="plan_text" class="form-control text-input"></td>' +
                    '<td><div class="ope-btn-wrap">'+
                    '</div></td>' +          
                '</tr>');
			this.initialize();
		}

		ProcessItem.prototype = {
			constructor: ProcessItem,
			initialize: function() {
				var _this = this;
				this.render();
				this.setEvent();
				setTimeout(function() {
					laydate({
						elem: '#begin_time_' + _this.id,
			            format: 'YYYY-MM-DD hh:mm:ss',
			            istime: true,
			            event: 'click',
			            fixed: false	
					});
					laydate({
						elem: '#end_time_' + _this.id,
			            format: 'YYYY-MM-DD hh:mm:ss',
			            istime: true,
			            event: 'click',
			            fixed: false	
					});				
				}, 500);
			},
			isEmptyData: function() {
				var data = this.data;
				if (!data.begin_time && !data.end_time && !data.plan_text) {
					return true;
				}
				return false;
			},
			render: function() {
				var data = this.data;
				this.El.find('.btime-input').val(data.begin_time || '');
				this.El.find('.etime-input').val(data.end_time || '');
				this.El.find('.text-input').val(data.plan_text || '');
				this.renderOperateBtns();
			},
			renderOperateBtns: function() {
				var btnsHtml = '<a href="javascript:;" id="insertRow" title="向下插入一行" class="button ope-btn"><i class="icon iconfont"></i></a>' + 
                    	(this.status == 'normal' ? '<a href="javascript:;" id="deleteRow" class="button ope-btn ml10"><i class="icon iconfont"></i></a>' : '');
                    
				this.El.find('.ope-btn-wrap').html(btnsHtml);
			},
			setStatus: function(status) {
				if (status != this.status) {
					this.status = status;
					this.renderOperateBtns();
				}
			},
			setEvent: function() {
				var _this = this;
				this.El.on('click', '#insertRow', function(e) {
					_this.trigger('insert');
				});
				this.El.on('click', '#deleteRow', function(e) {
					_this.El.remove();
					_this.trigger('delete');
				});
			},
			getData: function() {
				var begin_time_el = this.El.find('.btime-input'),
					end_time_el   = this.El.find('.etime-input'),
					plan_text_el  = this.El.find('.text-input');
				this.data.begin_time = $.trim(begin_time_el.val());
				this.data.end_time = $.trim(end_time_el.val());
				this.data.plan_text = $.trim(plan_text_el.val());
			},
			checkData: function(index, flag) {
				var begin_time_el = this.El.find('.btime-input'),
					end_time_el   = this.El.find('.etime-input'),
					plan_text_el  = this.El.find('.text-input');

				var previousProcessItem = processManager.getItem(index - 1);

				this.getData();
				var data = this.data, flag = flag;

				if (index >= 1 && processManager.checkIsEmpty(index)) {
					return true;
				}

				if (!this.isEmptyData() && flag) {
					flag = false;
				}

				if (!data.begin_time && !flag) {
					tip(begin_time_el, '开始时间不能为空');
					return false;
				}

				if (!data.end_time && !flag) {
					tip(end_time_el, '结束时间不能为空');
					return false;
				}

				if (!data.plan_text && !flag) {
					tip(plan_text_el, '流程内容不能为空');
					return false;
				}

				if (new Date(data.begin_time) > new Date(data.end_time) && !flag) {
					tip(begin_time_el, '本条设置的开始时间要早于结束时间');
					return false;
				}

				if (index >= 1 && !flag) {
					if (new Date(data.begin_time) < new Date(previousProcessItem.data.end_time)) {
						tip(begin_time_el, '开始时间要晚于上一条设置的结束时间');
						return false;
					}
				}

				if (!this.isEmptyData()) {
					var data = $.extend(this.data, true);
					delete data.id;
					return data;
				} else {
					return false;
				}
			}
		}

		Observe.make(ProcessItem.prototype);
		
		// 流程设置管理器
		var ProcessManager = function() {
			this.items = [];
			this.El = $('#processTable');
			this.initialize();
		}

		ProcessManager.prototype = {
			constructor: ProcessManager,
			initialize: function() {

			},
			insertRow: function(row) { // 往下插入一行
				var items = this.items;
				for (var i = 0; i < items.length; i++) {
					var flag = false;
					if (items[i].id == row.id) {
						/*if (i - 1 > 0) {
							if (items[i-1].isEmptyData()) {
								message('你已经插入了一行');
								flag = true;
							}
						}
						if (!flag) {*/
							this.addItem({}, i + 1);
						//}
						break;
					}
				}
			},
			refreshVisible: function() {
				var items = this.items;
				for (var i = 0; i < items.length; i++) {
					if (i == items.length - 1) {
						items[i].setStatus('noDelete');
					} else {
						items[i].setStatus('normal');
					}
				}
			},
			deleteRow: function(row) {
				var items = this.items;
				for (var i = 0; i < items.length; i++) {
					if (items[i].id == row.id) {
						this.items.splice(i, 1);
					}
				}
			},
			addItem: function(data, index, status) {
				var _this = this,
					index = index == 0 ? 0 : (index || this.items.length),
					processItem = new ProcessItem(data, status);

				processItem.on('delete', function() {
					_this.deleteRow(processItem);
					processManager.refreshVisible();
				});

				processItem.on('insert', function() {
					_this.insertRow(processItem);
					processManager.refreshVisible();
				});
				
				if (index >= this.items.length) {
					this.El.find('tbody').append(processItem.El);
				} else {
					this.El.find('tbody tr').eq(index).before(processItem.El);
				}	
				this.items.splice(index, 0, processItem);
			},
			setData: function(datas, status) {
				if ($.type(datas) != 'array')
					return;
				var _this = this;
				$.each(datas, function(index, data) {
					_this.addItem(data, undefined, status);
				})
			},
			checkIsEmpty: function(index) {
				var items = this.items, result = true;
				if (index >= 0 && index < items.length) {
					for (var i = index; i < items.length; i++) {
						if (!items[i].isEmptyData()) {
							result = false;
							break;
						}
					}
				}
				return result;
			},
			checkData: function() {
				var items = this.items,
					result = [],
					length = items.length;

				if (length == 1) {
					var itemData = items[0].checkData();
					if (itemData) {
						result.push(itemData);
					} else {
						return false;
					}
				} else {
					for (var i = 0 ; i < length; i++) {
						var itemData;
						if (i < length - 1) {
							itemData = items[i].checkData(i);
							if (!itemData)
								return false;
						} else if (i == length - 1) {
							itemData = items[i].checkData(i, true);
						}
						
						if (itemData && $.type(itemData) == 'object') {
							result.push(itemData);
						}
					}					
				}

				return result;
			},
			getItem: function(index) {
				var items = this.items;
				if (index <= items.length && index >=0) {
					return items[index];
				}
				return false;
			}
		}

		var processManager = new ProcessManager();

		function insertEmptyRow(status) {
			// 默认插入一个空值
			processManager.setData([{
				begin_time: '',
				end_time: '',
				plan_text: ''
			}], status);
		}
			
		El.find('#save').click(function() {
			var datas = processManager.checkData();
			if (datas) {
				var dialog = loading('正在加载中...');
				Server.addPlanList({
					activity: activity_id,
					activity_plans: JSON.stringify(datas),
					_token: _token
				}, function(resp) {
					dialog.close();
					if (resp.code == 0) {
						message('添加成功');
					} else {
						message(resp.message || '添加活动设置流程出错了!');
					}
				});
			}
		});

		var initFlag = false;

		return function() { // 执行非初始化的部分
			if (!initFlag) {
				initFlag = true;
				var dialog = loading('活动流程列表请求中...');
				// ajax获取数据
				Server.planList({activity: activity_id}, function(resp) {
					dialog.close();
					if (resp.code == 0) {
						if (resp.activities_plans) {
							processManager.setData(resp.activities_plans);
							var initLength = 3;
							for(var i = 1; i <= initLength; i ++) {
								insertEmptyRow();
							}
							processManager.refreshVisible();
						}					
					} else {
						message(resp.message || '获取活动设置流程出错了!');
					}
				});
			}
		}
		
	});

	// 主办方设置
	tabManager.make('sponsor', function(El, hasInit) {
		makeRoute('sponsor_hash');
		var conditionsManager = (function() {
			var conditions = [];

			function _add(value) {
				var flag = false;
				$.each(conditions, function(index) {
					if (conditions[index] == value) {
						flag = true;
					}
				})
				if (flag) return '';
				conditions.push(value);
				return '<div class="ui-infor">' +
	                    '<a href="javascript:;" class="ui-infor-link">'+ value +'</a>' +
	                    '<a href="javascript:;" class="badge del-btn"><i class="icon iconfont"></i></a>' +
	                '</div>';
			}

			function _remove(value) {
				for (var i = conditions.length-1; i>=0; i--) {
					if (conditions[i] == value) {
						conditions.splice(i, 1);
					}
				}
			}

			return {
				add: _add,
				remove: _remove,
				get: function() {
					return conditions;
				},
				getData: function() {
					var result = {};
					result.organizers = JSON.stringify(conditions);
					return result;
				}
			}
		})();

		var infors = El.find('#conditions');
		infors.on('click', '.del-btn', function(e) {
			var target = $(e.target),
				parent = target.parents('.ui-infor');
			parent.remove();
			var val = parent.find('.ui-infor-link').text();
			conditionsManager.remove(val);
		})

		// 添加主办方
		conditionInput = El.find('#conditionInput');
		El.find('#addCondition').click(function(){
			var value = $.trim(conditionInput.val());
			if (!value) {
				tip('#conditionInput', '主办方不能为空');
			} else {
				var aresult = conditionsManager.add(value);
				if (!aresult) {
					tip('#conditionInput', '该主办方已设置');
				} else {
					$('#conditions').append(aresult);
					conditionInput.val('');				
				}
			}
		});	

		// 确认主办方设置
		El.find('#sureBtn').click(function() {
			var conditions    = conditionsManager.getData();
			conditions._token = _token;
			conditions.id     = activity_id;
			conditions.update_step   = 5;
			var dialog        = Dialog.loading('正在修改中');
			// 修改主办方
			Server.updateActivity(conditions, function(resp) {
				dialog.close();
				if (resp.code == 0) {
					message('主办方设置成功');
				} else {
					message(resp.message || '修改主办方失败');
				}
			})
		});

		return function() {
			var dialog = loading('获取主办方列表...');
			// 获取数据
			Server.getActivity({
				activity: activity_id
			}, function(resp) {
				dialog.close();
				if (resp.code == 0) {
					if (resp.activity.organizers) {
						$.each(resp.activity.organizers, function(index, organizer) {
							var organizerEl = conditionsManager.add(organizer);
							$('#conditions').append(organizerEl);
						});
					}
				} else {
					message(resp.message || '获取主办方设置出错了！');	
				}
			})			
		};
	});

	// 签到二维码设置页
	tabManager.make('qrcode', function(El) {
		makeRoute('qrcode_hash');
		var signSurn  = El.find("#sign-sure"),
		    signInput = El.find("#sign-input"),
			batchDown = El.find("#batch-download");
			token     = _token;

		var parm = {
			'activity_id' : activity_id
		};

		function renderQrcodeTopage() {
			//设置加载动画
			var dialog = Dialog.loading("正在请求，请稍等。");
			Server.getCheckInQRCodes(parm,function(data) {
				dialog.close();
				if ( data.code == 0 ) {
					$('.sign-qrcode-content').html('');
					$.each(data.qrcodes,function(index, el) {
						$('.sign-qrcode-content').eq(index).append('<img src="'+el.url+'">');
					});
				}else {
					message(data.message);
				}
			});
		}

		//确定签到多少次
		signSurn.on('click', function(event) {
			event.preventDefault();
			var parm = {
				'activity_id' : activity_id,
				'qrcodes' : signInput.val(),
				'_token' : token
			};

			if ( parm.qrcodes > 10 ) {
				Dialog.alert('对不起，签到的次数不能超过<span style="color: red;">10次</span>，请重新输入。');
				return false;
			}

			//设置加载动画
			Dialog.loading("正在请求，请稍等。");

			//请求创建二维码的接口
			Server.createCheckInQRCodes(parm, function(data){
				Dialog.loading().close();//关闭加载动画
				if ( data.code == 0 ) {
					message("设置签到次数成功");
					renderQrcodeTopage();
				}else{
					message(data.message);
				}
			});
		});

		return function() {
			renderQrcodeTopage();
		}
	});

	// 签到管理页
    tabManager.make('sign', function(El) {
		makeRoute('sign_hash');
        var signSelect = El.find('#signSelect'),
            table;
            
        /*signSelect.find('.dropdown-menu').on('click', 'a', function(e) {
            var target = $(e.target);
            var qrcodeId = target.parent().attr('data-id');
            listMembersTableByQrcode(qrcodeId);
        });*/

        function listMembersTableByQrcode(qrcodeId) {
            if (table) {
                table.refresh({qrcodeId: qrcodeId});
            } else {
                table = Util.PagTable({
                    el: 'tableCon',
                    columnNameList: [
                        'index', 
                        'nick_name',
                        'mobile',
                        'created_at',
                    ],
                    source:[],
                    source: function(o, PagTable, option) {
                        var parms = {};
                        parms.activity_id = activity_id;
                        parms.size = 20;
                        if (option.refresh && option.refresh[0]) {
                            parms.step = option.refresh[0].qrcodeId;
                        } else {
                            parms.step = qrcodeId;
                        }
                        var dialog = loading('正在加载中');
                        Server.listMembersSignQrcode(parms, function(resp){
                            dialog.close();
                            if (resp.code == 0) {
                                PagTable({totalPage: Math.ceil(resp.total/20), datas: resp.check_ins});
                            } else {
                                alert(resp.message || '查询数据列表出错');
                            }
                        })
                    }
                });             
            }
        }
        
        return function() {
            if (qr_code_id > 0) {
                listMembersTableByQrcode(qr_code_id);
            }
            //var dialog = loading('正在加载中');
            // 获取签到二维码列表
            /*Server.getCheckInQRCodes({
                activity_id: activity_id
            }, function(data) {
                dialog.close();
                if ( data.code == 0 ) {
                    var dropdownMenu = signSelect.find('.dropdown-menu');
                    dropdownMenu.html('');
                    $.each(data.qrcodes, function(index, qrcode) {
                        var option = '<li data-id="'+ qrcode.id +'"><a href="javascript:;">签到二维码'+ qrcode.step +'</a></li>';
                        dropdownMenu.append(option);
                    });
                    dropdownMenu.find('a:eq(0)').click();
                }else {
                    message(data.message);
                }
            }); */      
        }
    });

	// 文档资料
	tabManager.make('file', function(El) {
		makeRoute('file_hash');
		var MAX_SIZE = 10 * 1024 * 1024;
		// 初始化Web Uploader
		var uploader = WebUploader.create({
		    auto: true,
		    fileVal: 'file',
		    swf: '/static/plugins/webuploader/Uploader.swf',
		    server: '/community/activity/file/add',
		    pick: '#uploadFile',
		    formData: {
		    	activity: activity_id,
		    	_token: _token
		    },
		    fileSingleSizeLimit: MAX_SIZE
		});

		var uploadLoading = El.find('#uploadLoadingWrap');

 		uploader.on( 'beforeFileQueued', function( file ) {
 			if (file.size > MAX_SIZE) {
 				message('上传文件不能超过10M');
 				return false;
 			}
 			uploadLoading.show();
 		});

		// 文件上传过程中创建进度条实时显示。
		/*uploader.on( 'uploadProgress', function( file, percentage ) { 
		    uploadProgressBar.show();
		    uploadProgress.css( 'width', percentage * 100 + '%' );
		});*/

		uploader.on( 'uploadSuccess', function( file, result ) {
		    uploadLoading.hide();
		    message('上传成功');
		    table.refresh();
		});

		uploader.on( 'uploadError', function( file ) {
		    uploadLoading.hide();
		    message('上传失败');
		});

		uploader.on('error', function( file ) {
			if (file == 'Q_TYPE_DENIED') {
				message('不支持该格式文件');
			} else {
				message('上传出错了');
			}
			uploadLoading.hide();
		});

		function addTableRow() {

		}

		var table;
		function listFilesTable() {
			if (table) {
				table.refresh();
			} else {
				table = Util.PagTable({
					el: 'fileTableCon',
					columnNameList: [
						'index', 
						function(data) {
							var url = '/static/images/', shortUrl = '';
							var extension = data.extension;
							switch(extension) {
								case 'excel' :
									shortUrl = 'excel.png'; break;
								case 'jpg' :
									shortUrl = 'jpg.png'; break;
								case 'png' :
									shortUrl = 'jpg.png'; break;	
								case 'pdf' :
									shortUrl = 'pdf.png'; break;
								case 'ppt' :
									shortUrl = 'ppt.png'; break;
								case 'word':
									shortUrl = 'word.png'; break;
								default:
									shortUrl = 'other.png'; break;
							}
							url += shortUrl;
							return '<div class="file-extension"><img src="'+ url +'" alt="" /></div>' +
								'<span class="file-name">'+ data.name +'</span>';
						},
						function(data) {
							return (data.size / (1024 * 1024)).toFixed(2);
						},
						function(data) {
							return '<a href="javascript:;" id="delete" class="ml10 button button-m button-pre"><i class="icon iconfont"></i>删除</a>';
						}
					],
					source: function(o, PagTable, option) {
						var parms = {};
						parms.activity = activity_id;
						parms.size = 20;
						Server.activityFileList(parms, function(resp){
							if (resp.code == 0) {
								PagTable({totalPage: resp.pages, datas: resp.files});
							} else {
								alert(resp.message || '查询数据列表出错');
							}
						})
					},
					events: {
						'click #delete': 'deleteHandler'
					},
					eventsHandler: {
						deleteHandler: function(e, row) {
							var parms = {};
							parms.activity = activity_id;
							parms.files = [];
							parms.files.push(row.data.id);
							parms._token = _token;
							var dialog = loading('正在删除文件...');
							Server.deleteActivityFile(parms, function(resp) {
								dialog.close();
								if (resp.code == 0) {
									message('已删除文件');
									row.destroy();
								} else {
									message(resp.message || '删除文件出错了');
								}
							})
						}
					}
				});				
			}
		}

		return function() {
			listFilesTable();
		}
	});

	// 路线管理
	tabManager.make('roadLine', function(El) { console.log('roadLine');
		makeRoute('roadLine_hash');
		var roadmap = [];
        var roadMap = new BMap.Map("roudMap");
        roadMap.centerAndZoom("成都", 12);
        roadMap.enableScrollWheelZoom();
        roadHelper = new MapHelper.DrawLines(roadMap);
        roadMap.addEventListener('click', function (e) {
            if (!e.overlay) {
                roadHelper.addMarker(e.point);
            }
        })

        roadMap.addEventListener('rightclick', function (e) {
            if (!e.overlay) { //点击到覆盖物后不做处理
                roadHelper.removeMarker();
            }
        })

        roadLocal = new BMap.LocalSearch(roadMap, {
            renderOptions: {map: roadMap},
            onMarkersSet: function (pois) { //成功以后回调
                for (var i = 0; i < pois.length; i++) {
                    pois[i].marker.addEventListener('click', function (e) {
                        roadMap.clearOverlays();
                        roadHelper.addMarker(e.target.point);
                    })
                }
            }
        })

        El.find("#roadSearch").click(function () {
            var value = $("#poiSearch").val().trim();
            if (value) {
                roadMap.clearOverlays();
                roadHelper.markers = [];
                roadHelper.points = [];
                roadHelper.draw();
                roadLocal.search(value);
            }
        })

        function drawRoadLine() {
	        if (roadmap.length) {
	            try {
	                for (var i = 0; i < roadmap.length; i++) {
	                    var point = new BMap.Point(roadmap[i][1], roadmap[i][0]);
	                    roadHelper.addMarker(point);
	                }
	                roadHelper.draw();                        
	            } catch (e) {

	            }
	        }
        }

	    function getStringifyLinePoints() {
	        var result = [];
	        if (roadHelper && roadHelper.points) {
	            $.each(roadHelper.points, function(i, point) {
	                result.push([point.lat, point.lng]);
	            })
	            return JSON.stringify(result || '');
	        }
	        return '';
	    }

        if (activity_id) {
	        server.getActivity({activity: activity_id}, function(resp) {
	            if (resp.code == 0) {
	                roadmap = resp.activity.roadmap || [];
	                drawRoadLine();
	            } else {
	                Dialog.message(resp.message || '获取活动地图数据失败,试试刷新重新获取');
	            }
	        })        	
        }

        El.find('#save').click(function() {
        	roadmap = getStringifyLinePoints();
        	if (!roadmap.length) {
        		Dialog.message('你还没有设置路线');
        	} else {
			    var dialog = Dialog.loading('修改中...');
			    var datas = {
			    	id: activity_id,
			    	_token: _token,
			    	roadmap: roadmap
			    };
			    server.updateActivity(datas, function(resp) {
			        dialog.close();
			        if (resp.code == 0) {
			            Dialog.message('修改成功');
			        } else {
			            Dialog.message(resp.message || '服务器出错了!');
			        }
			    })
        	}
        })

		return function() {
			// 
		}
	});

	var page = {
		// program entry
		initialize: function() {
			var hash = location.hash ? location.hash.split('#')[1] : 'process_hash';
			tabManager.select(hash.split('_')[0]);
		}
	}

	page.initialize();

})