$(function(){
	var parms = {},
		payData ;
		K = K;
	parms.order_no = $('#order_no').val();
	parms.mobile = $('#mobile').val();
	parms.openid = $('#openid').val();

	//点击微信支付
	$('.pay-weixin').on('click', function(event) {
		if(!K.isWeiXin()){
			oAlert('请在微信中打开，才能使用微信支付。');
			return false;
		}
		$.ajax({
			url: '/api/payment/wxpay/web/prepay',
			type: 'POST',
			dataType: 'json',
			data: parms,
			success: function(data){
                if (data.code != 0) {
                    alert(data.message); 
                } else {
                    payData = data;
                    console.log(payData);
                    pay();
                }
			},
			error: function(xhr, errorType, error){
					console.log(error);
			}
		})
	});

	function onBridgeReady(){
        WeixinJSBridge.invoke(
            'getBrandWCPayRequest', {
               "appId" : payData.appId,     //公众号名称，由商户传入     
                "timeStamp": payData.timeStamp,         //时间戳，自1970年以来的秒数     
                "nonceStr" : payData.nonceStr, //随机串     
                "package" : payData.package,     
                "signType" : payData.signType,         //微信签名方式:     
                "paySign" : payData.paySign //微信签名 
           },
            function(res){
 				if (res.err_msg == "get_brand_wcpay_request:ok") {
 					window.location.href = '/wap/activity/userStatus?activity_id=' + $('#aid').val() + '&status=3' + '&mobile=' + parms.mobile;
                } else if (res.err_msg == 'get_brand_wcpay_request:cancel') {
                    // user cancel, do nothing
 				} else {
                    alert('支付失败');
                }
           }
       ); 
     }
	function pay(){  
	    if (typeof WeixinJSBridge == "undefined"){
	       if( document.addEventListener ){
	             document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
	         }else if (document.attachEvent){
	             document.attachEvent('WeixinJSBridgeReady', onBridgeReady); 
	            document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
	        }
	     }else{
	       onBridgeReady();
	     }
 	}
});
