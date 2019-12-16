<?php
/**
 * Created by PhpStorm.
 * User: daxin
 * Date: 2017/8/1
 * Time: 下午4:17
 */
namespace Home\Controller;

use Think\Controller;

class ViewController extends Controller
{
    public function index()
    {
        $this->display('Product/search');
    }
    public function index2()
    {
        $this->display('Product/detail');
    }
    public function index3()
    {
        $this->display('Index/release');
    }
    public function index4()
    {
        $this->display('Index/brand');
    }
    public function index5()
    {
        $this->display('Index/catalogue');
    }
    public function index6()
    {
        $this->display('Index/feedback');
    }
    public function cart()
    {
        $this->display('Cart/cart');
    }
    public function cart2()
    {
        $this->display('Cart/cartOrder');
    }
    public function cart3()
    {
        $this->display('Cart/orderSuccee');
    }
    public function cart4()
    {
        $this->display('Cart/checkstand');
    }
    public function cart5()
    {
        $this->display('Cart/weixinPay');
    }
    public function cart6()
    {
        $this->display('Cart/alipay');
    }
    public function center()
    {
        $this->display('Order/centerUser');
    }
    public function center1()
    {
        $this->display('Order/earnest');
    }
    public function center2()
    {
        $this->display('Order/payment');
    }
    public function center3()
    {
        $this->display('Order/delivery');
    }
    public function center4()
    {
        $this->display('Order/appraise');
    }
    public function center5()
    {
        $this->display('Order/myOrder');
    }
    public function center6()
    {
        $this->display('Order/myAppraise');
    }
    public function center7()
    {
        $this->display('Order/noInvoice');
    }
    public function center8()
    {
        $this->display('Order/hasInvoice');
    }
    public function center9()
    {
        $this->display('Order/setSpecial');
    }
    public function center10()
    {
        $this->display('Order/setGeneral');
    }
    public function center11()
    {
        $this->display('Order/orderDetail');
    }
    public function center12()
    {
        $this->display('Order/comment');
    }
    public function center13()
    {
        $this->display('Order/retreatCargo');
    }
    public function center14()
    {
        $this->display('Order/applyCargo');
    }
    public function center15()
    {
        $this->display('Order/returnComplete');
    }
    public function center16()
    {
        $this->display('Order/buyerReturn');
    }
    public function center17()
    {
        $this->display('Order/retreatLogistic');
    }
    public function center18()
    {
        $this->display('Order/checkLogistic');
    }
    public function center19()
    {
        $this->display('Order/paymentLimit');
    }
    public function center20()
    {
        $this->display('Order/paymentApply');
    }
    public function center21()
    {
        $this->display('Order/paymentApplyFirm');
    }
    public function center22()
    {
        $this->display('Order/paymentOrder');
    }
    public function center23()
    {
        $this->display('Order/centerCompany');
    }
    public function center24()
    {
        $this->display('Order/applyComplete');
    }

    public function example()
    {
        $this->display('Index/example');
    }
    public function info_normal()
    {
        $this->display('User/info_normal');
    }
    public function delivery_address()
    {
        $this->display('User/delivery_address');
    }
    public function binding_phone()
    {
        $this->display('User/binding_phone');
    }
    public function change_password()
    {
        $this->display('User/change_password');
    }
    public function feedback()
    {
        $this->display('User/feedback');
    }
    public function favorite()
    {
        $this->display('User/favorite');
    }
    public function unite_yes_normal()
    {
        $this->display('Account/unite_yes_normal');
    }
    public function unite_no_normal()
    {
        $this->display('Account/unite_no_normal');
    }
    public function unite_no_company()
    {
        $this->display('Account/unite_no_company');
    }
    public function unite_yes_company()
    {
        $this->display('Account/unite_yes_company');
    }
    public function bind_account()
    {
        $this->display('User/bind_account');
    }
    public function register_success()
    {
        $this->display('Account/register_success');
    }

    public function find_success()
    {
        $this->display('Account/find_success');
    }
    public function info_company()
    {
        $this->display('User/info_company');
    }
    public function forget_paw_success()
    {
        $this->display('Account/forget_paw_success');
    }
    public function bind_success()
    {
        $this->display('Account/bind_success');
    }
}