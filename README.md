# -
first part
//apiCloud支付配置
function alipay02(){
 	api.hideProgress();
 	isclick=false;
	 	aliPay = api.require('aliPay');
		aliPay.config({
		    partner: '2088621941225248',
		    seller: 'shihuigou780@163.com',
		    //rsaPriKey: 'MIICXQIBAAKBgQDcs+lUSgTX1fQDU3xKV5VEuF57QQbWPN5qw4izBXwAWs4RbtbQCH8NO4d0e5Rkm6F6mOjz2fY8orDnPZ2vStwqWuI2f/fktZ0AA0saMyor+weeEe4fX+8S7zBecyLmttTRNLzOtG9vxM6tXMmeFabVW4TarxScsF+OP/WzBznOWwIDAQABAoGBAIN4qQYNEdWBHlrc4K1ofwLw0Vea5Pe6SsROtp/uJHARp6+61zwV05mOXKKG+17zVr4xWJPqw0RbpgYaLlS9w2hcOEGDjny8bGmq4NyLmGkxQGxyzI1qIUqLm4Et5SLx59af/XlV2cIj2LcXIpBcJgkhTR/ITyVINtc9kD3wKBwBAkEA+T1z3yg8/8xkiEEfqszXTDrE59dXYfPZk2gtyuop8xwUk0yrEmhq9lTWz8o2RzkKra19aUwDgIsuLhMp/bo7JQJBAOKwUOyquNhguYtWT29BtLlwU19u6TUSYVpGAgMKCxeV2R3y6Sv5bpRilFk1tDH0QxOs+Gkl4uVc+2yx4gFGa38CQCvPoY6YhCByzTkmOWrMlwvPSM14DOQq+RPwPBxvDPCu/u7liyyxLhwezaO459GdNUNSO7lGo1b1ICj5NWhkVAECQQCKhTE+HWiMmDZpJZmuo5j6w9++bjjFPHEOx77M+qMii2e7/EZtn6Lpu39pL/7nk5o1eLnnDsaiX3onxl8TZmOjAkB+ICIcXfCfrtpAfu1p2letzGYeM3G5OrQ3L06k7dqEWfOxvM96L47JDobVUazmIC4gJFPdCizWe1fDX7Zw4vZe',
		    //'MIICXQIBAAKBgQDcs+lUSgTX1fQDU3xKV5VEuF57QQbWPN5qw4izBXwAWs4RbtbQCH8NO4d0e5Rkm6F6mOjz2fY8orDnPZ2vStwqWuI2f/fktZ0AA0saMyor+weeEe4fX+8S7zBecyLmttTRNLzOtG9vxM6tXMmeFabVW4TarxScsF+OP/WzBznOWwIDAQABAoGBAIN4qQYNEdWBHlrc4K1ofwLw0Vea5Pe6SsROtp/uJHARp6+61zwV05mOXKKG+17zVr4xWJPqw0RbpgYaLlS9w2hcOEGDjny8bGmq4NyLmGkxQGxyzI1qIUqLm4Et5SLx59af/XlV2cIj2LcXIpBcJgkhTR/ITyVINtc9kD3wKBwBAkEA+T1z3yg8/8xkiEEfqszXTDrE59dXYfPZk2gtyuop8xwUk0yrEmhq9lTWz8o2RzkKra19aUwDgIsuLhMp/bo7JQJBAOKwUOyquNhguYtWT29BtLlwU19u6TUSYVpGAgMKCxeV2R3y6Sv5bpRilFk1tDH0QxOs+Gkl4uVc+2yx4gFGa38CQCvPoY6YhCByzTkmOWrMlwvPSM14DOQq+RPwPBxvDPCu/u7liyyxLhwezaO459GdNUNSO7lGo1b1ICj5NWhkVAECQQCKhTE+HWiMmDZpJZmuo5j6w9++bjjFPHEOx77M+qMii2e7/EZtn6Lpu39pL/7nk5o1eLnnDsaiX3onxl8TZmOjAkB+ICIcXfCfrtpAfu1p2letzGYeM3G5OrQ3L06k7dqEWfOxvM96L47JDobVUazmIC4gJFPdCizWe1fDX7Zw4vZe',//
		    rsaPriKey: 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBANDg6Jb1qWb1qbzoHFGVc2bC0g6XE619+oAG7i05DS8BQJBP8ccXBP471/vwV6hDeW3EoaJT4idq4s9HaP4O2WNQfFvYGIRsu36c9akgfAn0F2oC7lRvoma9HdVbwF8+n4SeZAfTfMWPrtJBTlhUG0SFWcTug0LHcz9tScvJhOQ9AgMBAAECgYBksigurltuQTwEz7jnM68geQce9YIM/1CF69Fih8BtSqM/burV2akUjvD+ic0YVv7xBfwN73Z1HjgdSQW6hJoTQbUIperdrHN/LCZsojcrMnZfb8LlAWCaDegfSYJadDU3lNDeKTczaQrYJ3Z76JzP/+g3N3jWZqVZqQIZTKKbGQJBAOxe3Q1xJDNqoatMS89m7rLeUnLVwI1DfMYH+QPd59YnOvZkaQoAusTTu3G8sQr0jYKLtDk+vm0IokhyX/QDLysCQQDiOZOj+ZcaA2tk5yZweWyHZZPHHIJpk8wkimsBFx5m/3G3W5hq5cdXuqbomfjW9e5Y51IZqTQXS3r6xhAB0EY3AkBRciaNGS02Iknusm1026zoKT8Tnp+ojVaTDfA56t6VphLlD5g6ACJa6/IssK34bmfMUcMZ7orDGzR/7hkuBWLdAkBqqoU/uq6RWG1pzUelnssaaD2uk3W2PDb0P8PGZtUx8V33+5s5RBCi/+I1KGxZRupURvXCHbLvDOr2lS70+/QvAkEA0ikQ+gnN2hcRr1gFqJKe43uVvXdu0ycH08DwqpNLimUoGN/5Z9/G0gu4z58oJiooXaSDUz7vxUrZ3lS0U1GllA==',
		    rsaPubKey: 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB',
		    notifyURL: 'http://m.lefpay.com/pay/alipay_notify_url.aspx'
		},function(ret, err) {
		//alert(JSON.stringify(ret));
		aliPay.pay({
	    subject: api.pageParam.orderid,//'订单名',
	    body: api.pageParam.orderid,//'订单描述',
	    amount: api.pageParam.money,//'0.01',
	    tradeNO: '4563548735674'+Math.floor(Math.random()*1000)
	}, function(ret, err) {//alert(JSON.stringify(ret));
		var msg=""; 
		isclick=true;	
		var codes=Number(ret.code);
		if(codes==9000){
			msg="支付成功...";
		}else{
			msg="支付失败...";
		};
	    api.alert({
	        title: '支付结果',
	        msg: msg,
	        buttons: ['确定']	        
	   	 },function(ret){//alert("支付宝"+JSON.stringify(ret));
	   	 	var index=Number(ret.buttonIndex);
	   	 	if(index==1){	//alert("eurl"+eurl);   	 
	   	 		if(eurl.toLowerCase().indexOf("http:")>-1 && codes==9000)
					       {//alert("支付宝ok");					       
					     	api.execScript({
					     		 name:'root',
	                             script: 'framelaodurl("'+eurl+'")'
                             });
                            }                           
                            layer.closeAll();                   
					        api.closeFrame();
	   	 	}
	   	 });
		});   
	  });
 	}
