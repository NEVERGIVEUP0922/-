//app.js
var e = require("utils/core.js");
var $ = require("utils/jquery.js");
App({
  onLaunch: function () {return;
    var userinfo = this.getCache("userinfo");
    ("" == userinfo || userinfo.needauth) && this.getUserInfo(function (e) {
        e = e || "";
      //页面重定向
      wx.redirectTo({
        url: "/pages/message/auth/index?text=" + e
      })
    })
  },
  requirejs: function (e) {
    return require("utils/" + e + ".js");
  },
  getCache: function (e, t) {
    var i = +new Date / 1000,
      n = "";
    i = parseInt(i);
    try {
      n = wx.getStorageSync(e + this.globalData.appid);
     // console.log(e + this.globalData.appid);
        //n.expire > i || 0 == n.expire ? n = n.value : (n = "", this.removeCache(e))
    } catch (e) {
      n = void 0 === t ? "" : t
    }
    return n = n.value || ""
  },
  setCache: function (e, t, i) {
    var n = +new Date / 1000,
      a = true,
      o = {
        expire: i ? n + parseInt(i) : 0,
        value: t
      };
    try {
      wx.setStorageSync(e + this.globalData.appid, o)
    } catch (e) {
      a = false
    }
    return a
  },
  removeCache: function (e) {
    var t = true;
    try {
      wx.removeStorageSync(e + this.globalData.appid)
    } catch (e) {
      t = false
    }
    return t
  },
  getUserInfo: function (i,referr,reSet) {
    var _this = this;
    wx.login({
      success: function (res) {
        if (res.code) {
          wx.getUserInfo({
            success: function (res2) {
              console.log(res2);
              var encryptedData = res2.encryptedData;
              var iv = res2.iv;
              if (encryptedData != undefined && encryptedData != null && iv != undefined && iv != null) {

                var userInfo = res2.userInfo
                _this.globalData.nickName = userInfo.nickName;
                _this.globalData.headurl = userInfo.avatarUrl;
                _this.globalData.userInfo = userInfo.userInfo;
                //console.log(res.code + "//" + encryptedData + "//" + iv);
                //获取token
                wx.request({
                  url: _this.globalData.daxin + "/login/getOpenid",
                  data: {
                    code: res.code, iv: iv, encryptedData: encryptedData, open_key: _this.globalData.open_key
                  },
                  method: "POST",
                  success: function (res) {
                    _this.setCache("userInfo", { headurl: userInfo.avatarUrl, nickName: userInfo.nickName,session_token: res.data.session_token}, 7200);
                    _this.globalData.session_token = res.data.session_token;
                    if (res.statusCode == 200 && res.data.statusCode >= 0){
                      referr(res.data.data.user_info);

                    }else{
                      if (res.data.statusCode == -500) { referr(res.data.data.user_info);return};
                      e.alert(res.data.msg);
                      i(res.errMsg);
                    }
                  },complete: function (res4) {
                    console.log(res4);
                  }
                })


                
              }
              else {
                e.alert(res.errMsg);
                i(res.errMsg);
               // getApp().globalData.ischeck = false;
               // getApp().tips("登录失败");
              }
              
            },fail:function(){
              let userinfo = _this.getCache("userInfo");

              if (!userinfo.session_token && reSet == "true"){
                i("需要授权才能查看");
              }else{
                wx.switchTab({
                  url: '/pages/index/index'
                })
              };
             

            }, complete:function(res3){
              wx.showLoading({
                title: '加载中...',
                success:function(){
                  setTimeout(function () {
                    wx.hideLoading()
                  }, 2000)
                }
              })
                console.log(res3);
            }
              
              })    
        } else {
          e.alert(res.errMsg);
         // console.log('登录失败！' + res.errMsg)
        }
      }
    });
     
  },
  getSet: function () {
    wx.getSetting({
      success: function (res) {
       // console.log(res);
      }})
  },
  url: function (e) {
    e = e || {};
    var t = {},
      i = "",
      n = "",
      a = this.getCache("usermid");
    i = e.mid || "",
      n = e.merchid || "",
      "" != a ? ("" != a.mid && void 0 !== a.mid || (t.mid = i), "" != a.merchid && void 0 !== a.merchid || (t.merchid = n)) : (t.mid = i, t.merchid = n),
      this.setCache("usermid", t, 7200)
  },
  getPrice: function (rangePrices, num) {
    var price=0;
    var lengt = rangePrices.length - 1;
    $.each(rangePrices,function(ind,val){
      var left = parseFloat(val.lft_num);
      var right = parseFloat(val.right_num);
      if (ind == 0) {
        if ((num < right)) {
          price = val.unit_price;
          return false;
        };

      } else if (ind == lengt){
        if ((num >= left) && (right == "0")) {
          price = val.unit_price;
          return false;
        };
      }else{
        if (num >= left) {
          if (right > num) {
            if (right != "0") {
              price = val.unit_price;
              return false;
            }
          }
        }
      };
      });
    return price;
  },
  getOrderStatus: function ($order, $return_type){
    var $pay_status = [
      '未付款',
      '部分支付',
      '全部支付',
    ];
    var $ship_status = [
       '待发货',
       '部分发货',
       '待收货',
       '部分收货',
       '全部收货',
    ];
    var $order_status = [
       '新单',
       '锁单',
       '部分完成(有付款)',
       '完成',
    ];
    var $ship_type = [
      "无",
      '快递',
      '物流',
      '自取',
      '送货'
    ];
    var $pay_type = [
      "无",
      "在线支付",
      "账期支付",
      "快递代收",
      "面对面付款",
      "银行转账",
      "线下支付"
    ];
    var $knot_status = { 0: "返差额申请中", 1: "返差额申请已通过", 5: "返差额申请失败", 20:"返差额已完成"};

    if ($return_type == 'check_status'){
      return knot_status[$order['ship_type']];
    };
    if ($return_type=='ship_type'){
      return $ship_type[$order[$return_type]];
    };
    if ($return_type == 'pay_type' || $return_type == 'deposits_pay_type') {
      return $pay_type[$order[$return_type]];
    };
    if ($return_type == "ship_status"){
      var num = $order[$return_type],shipStatus="";
      switch(num){
        case 0: shipStatus ="待发货";break;
        case 1: shipStatus = "部分发货"; break;
        case 2: shipStatus = "待收货"; break;
        case 3: shipStatus = "部分收货"; break;
        case 4: shipStatus = "全部收货"; break;
        default: shipStatus = "无货物状态";
      };
      return shipStatus;
    };
  if ($order['order_status'] == '1') {
    if ($return_type) return 1;
     return '待审核';
  } else if($order['order_status'] == '3' || ($order['pay_status'] == '2' && $order['ship_status'] == '4') ){
    if ($order['is_comment'] == '1') {
      return '已完成';
    } else {
      return '已完成';
    }
  }else{
      //水单的
      if ($order['pay_status'] == '0') {
        if (($order['pay_type'] == '5' ) && $order['payImg'] ) {
          $img = $order['payImg'][$order['payImg'].length - 1];
          if ($img['status'] == '0' ) {
            if ($return_type) return 1;
            return '水单待审核,'.$ship_status[$order['ship_status']];
          } else if ($img['status'] == '1' ) {
            return '水单不通过';
          } else {
            return '水单通过';
          }
        }
      }
      //在线支付
      if ($order['pay_type'] == '1' ){
        //不是已全部付款 都是未付
        if ($order['pay_status'] != '2' ){
          return '待付款';
        }else{
          //全部收货
          if ($order['ship_status'] == '2' ){
            return '待收货';
          }
          return $order['ship_status'] == 100 ? "待发货" : $ship_status[$order['ship_status']];
        }
      }else{
        //其他支付方式都属于线下
        if ($order['pay_status'] == '0' || $order['pay_status'] == '1'  ){
          return $pay_status[$order['pay_status']]+','+$ship_status[$order['ship_status']]+'(付款状态更新可能延迟!)';
        }else{
          return $order['ship_status'] ==100?"待发货":$ship_status[$order['ship_status']];
        }
      }
  }
},
getRetreatstatus:function(handleStatus){
  var status='';
  switch (handleStatus){
    case 1: status = '审核中';break;
    case 2: status = '审核通过，待退货'; break;
    case 3: status = '驳回,协商'; break;
    case 4: status = '退货运输中'; break;
    case 5: status = '已收货，待退款'; break;
    case 6: status = '已完成'; break;
    case 7: status = '退货退款款撤销'; break;
    case 8: status = '部分审核通过'; break;
  }
  return status;
},
getRetreat:function(order_retreat,order_good){
  if (!(order_retreat instanceof Array)) { return "order_retreat不是数组";}
  if (!(order_good instanceof Array)) { return "order_good不是数组"; }
  $.each(order_good,function(index,self){
    var retreat_nums= 0;
    $.each(order_retreat, function (ind, value) {
      if (value.handle_status == 6 && value.item ) {
        $.each(value.item, function (id, val) {
          if (self.p_id == val.p_id) {
            retreat_nums += parseFloat(val.p_num);
          };
        });
      }
    });
    self.has_retreat_num = retreat_nums;
  });
  return order_good;
},
getShareData:function(code){
  //code = "db175088523291370283424718081301";
  if (typeof code != "string"){return {}};
  var lengthObj = { a: 1, b: 2, c: 3, d: 4, e: 5, f: 6, g: 7, h: 8, i: 9, j: 10, k: 10};
  var uid_length = code.substr(0,1);
  var pid_length = code.substr(1, 1);
  var share_type = { "01":"/pages/goods/detail/index"}
  var data = {};
  data.uid = code.substr(2, lengthObj[uid_length]);
  data.pid = code.substr(13, lengthObj[pid_length]);
  var keys = code.substr((code.length -2), 2) + "";
  data.shareUrl = share_type[keys] + "?id=" + data.pid;
  return data;
},
count: function (obj, is_involist, payType,extra) {
    //优惠价格计算
    var _this=this;
    var is_desposite = extra.order_type||(_this.data.sendData.order_type);
    var desposite_type = extra.deposits_pay_type || (_this.data?_this.data.depositeChoose:"0");
    var returnObj = $.extend({}, obj);
    var origin_price = obj.product_price ? _this.getPrice(obj.product_price, obj.num) : obj.p_price_true;
    is_involist = is_involist || extra.is_invoice;
    payType = payType || extra.pay_type;
    if (obj.user_product_bargain) {
      //优惠
      if (payType == 1 || payType == 5) {
        //在线支付
        if (obj.user_product_bargain.discount_price > 0 && obj.user_product_bargain.discount_price_invoice_change == 0 && obj.user_product_bargain.discount_price_tax == 0) {
          //只有优惠未税
          returnObj.price = (obj.user_product_bargain.discount_price * (returnObj.tax > 0 ? returnObj.tax : 1.1));
          returnObj.total = returnObj.price * returnObj.num;
        } else {
          returnObj.price = (obj.user_product_bargain.discount_price_invoice_change > 0 ? obj.user_product_bargain.discount_price_invoice_change : obj.user_product_bargain.discount_price_tax);
          returnObj.total = returnObj.price * returnObj.num;
        }
      } else {
        //非在线支付
        if (is_involist > 0) {
          //开票非在线支付按照开票在线支付执行
          if (obj.user_product_bargain.discount_price > 0 && obj.user_product_bargain.discount_price_invoice_change == 0 && obj.user_product_bargain.discount_price_tax == 0) {
            //只有优惠未税
            returnObj.price = (obj.user_product_bargain.discount_price * (returnObj.tax > 0 ? returnObj.tax : 1.1));
            returnObj.total = returnObj.price * returnObj.num;
          } else {
            returnObj.price = (obj.user_product_bargain.discount_price_invoice_change > 0 ? obj.user_product_bargain.discount_price_invoice_change : obj.user_product_bargain.discount_price_tax);
            returnObj.total = returnObj.price * returnObj.num;
          }
        } else {
          if (obj.user_product_bargain.discount_price) {
            returnObj.price = obj.user_product_bargain.discount_price * (returnObj.tax > 0 ? returnObj.tax : 1.1);
            returnObj.total = obj.user_product_bargain.discount_price * returnObj.num;
          } else {
            returnObj.price = obj.user_product_bargain.discount_price_invoice_change > 0 ? obj.user_product_bargain.discount_price_invoice_change : obj.user_product_bargain.discount_price_tax;
            if (returnObj.discount_num > 0 && returnObj.price * returnObj.num > returnObj.discount_num) {
              //折扣限
              if (is_desposite > 0 && desposite_type == 1) {
                returnObj.total = origin_price * returnObj.num;
              } else {
                returnObj.total = origin_price * returnObj.num / (returnObj.tax > 0 ? returnObj.tax : 1.1);
              }
            } else {
              returnObj.total = origin_price * returnObj.num;
            }
          }
        }
      }
    } else {
      //没优惠
      if (payType == 1 || payType == 5) {
        //在线支付
        returnObj.price = origin_price;
        returnObj.total = origin_price * returnObj.num;
      } else {
        //非在线支付
        if (is_involist > 0) {
          //开票非在线支付同开票在线支付
          returnObj.price = origin_price;
          returnObj.total = origin_price * returnObj.num;
        }
        returnObj.price = origin_price;
        if (returnObj.discount_num > 0 && origin_price * returnObj.num > returnObj.discount_num) {
          //折扣限
          if (is_desposite > 0 && desposite_type == 1) {
            returnObj.total = origin_price * returnObj.num;
          } else {
            returnObj.total = origin_price * returnObj.num / (returnObj.tax > 0 ? returnObj.tax : 1.1);
          }
        } else {
          returnObj.total = origin_price * returnObj.num;
        }
      }
    }
    if (returnObj.earnest_scale) returnObj.earnest = returnObj.total * returnObj.earnest_scale;
    return returnObj;
  },
  globalData: {
    appid: "wx6a1319c215b146fd",
    api: "https://wx.longicmall.com/",
    uploadimg: "https://wx.longicmall.com",
    //uploadimg: "http://www.daxin.com",
    domain:"https://wx.longicmall.com/small",
    localhost:"",
    daxin:"https://wx.longicmall.com/small",
    daxinImg: "https://www.longicmall.com/",
    //daxinImg: "http://www.daxin.com/",
    pdfUrl:"https://wx.longicmall.com",
    daxin: "https://wx.longicmall.com/small",
    //daxin: "http://www.daxinshop.com/small",
    open_key: 'b0326110cb13e94de6654f9686d7f633',
    userInfo: null,
    imgs:{main_index:[]}
  }
})
