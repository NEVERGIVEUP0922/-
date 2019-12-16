<?php
// +----------------------------------------------------------------------
// | FileName:   OrderInvoiceController.class.php
// +----------------------------------------------------------------------
// | Dscription:   订单发票管理
// +----------------------------------------------------------------------
// | Date:  2018-01-25 10:01
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace  Admin\Controller;
use Admin\Model\UserInvoiceModel;
use Admin\Model\UserModel;
use Common\Controller\BaseController;
use Common\Controller\Category;

class OrderInvoiceController extends AdminController
{

    protected $adminId;

    public function _initialize()
    {
        parent::_initialize();
        $this->adminId = session('adminInfo.uid');
    }


    // 查询业务员下所有客户发票列表
    public function index()
    {
        $query = I('get.');
        $page=I('get.page')?I('get.page'):1;
        $pageSize=I('get.pageSize')?I('get.pageSize'):C('PAGE_PAGESIZE');
        $where = '';
        $whereList = [];
        /*
         * status   0 未开(申请) 1 正在处理 2已开
         * user     用户搜索
         * cust     erp客户搜索
         */
//        foreach( $query as $k=>$v ){
//            if( $v ){
//                switch ( $v ){
//                    case 'status':
//
//                }
//            }
//        }

        $m = new UserModel();
        $userList = $m->userSlaveCustomerList( $this->adminId );
        if( $userList['error'] !== 0 ){
            $this->assign( 'list', [] );
            $this->display();
        }
        $where = 'user_id in ('.implode(',',$userList['data']).')';
        $res = D('UserInvoice')->getInvoiceList( $where,$page, $pageSize);
        if( $res ){
            foreach( $res['list'] as $key=>$value ){
                $res['list'][$key]['user_name'] = $value['userInfo']['user_name'];
                $res['list'][$key]['fcustno'] = $value['userInfo']['fcustno'];
                $res['list'][$key]['fcustjc'] = $value['userInfo']['fcustjc'];
                $sysUser = M('','sys_user')->field('FEmplName')->find( $value['userInfo']['sys_uid'] );
                $res['list'][$key]['sys_name'] = $sysUser['femplname']?$sysUser['femplname']:'';
            }
            $res['page'] = $page;
            $res['pageSize'] = $pageSize;
        }else{
            $res = [];
        }
//        de( $res );
        $this->assign( 'list', $res );
        $this->display();

    }


}