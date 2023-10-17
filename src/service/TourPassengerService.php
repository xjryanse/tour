<?php

namespace xjryanse\tour\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\user\service\UserPassengerService;
use xjryanse\user\service\UserService;
use xjryanse\order\service\OrderService;
use app\circuit\service\CircuitBusService;
use xjryanse\logic\Strings;
use xjryanse\logic\DataCheck;
use xjryanse\logic\Debug;
use xjryanse\logic\Arrays;
use Exception;

/**
 * 
 */
class TourPassengerService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\tour\\model\\TourPassenger';

    public static function extraPreSave(&$data, $uuid) {
        self::checkTransaction();
        // DataCheck::must($data,['order_id','seat_no','passenger_id','prize']);
        $passengerId = Arrays::value($data, 'passenger_id');
        // 20230215：移入内部判断
        if ($passengerId) {
            $passenger = UserPassengerService::getInstance($passengerId)->get();
            if (!$passenger) {
                throw new Exception('人员数据不存在' . $passengerId);
            }

            $data['realname'] = $passenger['realname'];
            $data['id_no'] = $passenger['id_no'];
            $data['phone'] = $passenger['phone'];
        }

        // 2023-02-27
        $phone = Arrays::value($data, 'phone');
        if (!Strings::isPhone($phone)) {
            throw new Exception('手机号码格式错误' . $phone);
        }
        // 通过手机号码关联出用户的id
        $data['user_id'] = UserService::phoneUserId($phone);

        if (self::hasRegist(Arrays::value($data, 'order_id')
                        , Arrays::value($data, 'tour_time_id')
                        , Arrays::value($data, 'realname')
                        , Arrays::value($data, 'id_no')
                        , Arrays::value($data, 'phone'))) {
            throw new Exception(Arrays::value($data, 'realname') . '已在本团次登记');
        }

        Debug::debug('保存数据', $data);
        return $data;
    }

    /**
     * 用户是否在当前团次登记
     * @param type $orderId
     * @param type $tourTimeId
     * @param type $realname
     * @param type $idNo
     * @param type $phone
     * @return type
     */
    public static function hasRegist($orderId, $tourTimeId, $realname, $idNo, $phone) {
        // 有团次号，用团次号当条件
        if ($tourTimeId) {
            $con[] = ['tour_time_id', '=', $tourTimeId];
        } else {
            $con[] = ['order_id', '=', $orderId];
        }
        // 有身份证号，用身份证号当条件
        if ($idNo) {
            $con[] = ['id_no', '=', $idNo];
        } else {
            $con[] = ['realname', '=', $realname];
            $con[] = ['phone', '=', $phone];
        }
        // 2023-02-28:退再买
        $con[] = ['is_ref', '=', 0];

        $count = self::where($con)->count();
        return $count ? true : false;
    }

    /**
     * 2023-03-03添加订单的成团人员
     * @param type $orderId
     * @param type $passengerArr
     * @param type $data
     * @return type
     * @throws Exception
     */
    public static function orderPassengerAdd($orderId, $passengerArr, $data = []) {
        $cond[] = ['order_id', '=', $orderId];
        $count = self::count($cond);
        if ($count) {
            throw new Exception('订单已有乘客，不可重复添加');
        }
        foreach ($passengerArr as $passenger) {
            Debug::debug('$passenger', $passenger);
            $tmpData = $data;
            $tmpData['order_id'] = $orderId;
            // $tmpData['seat_no']         = $passenger['seat_no'];
            $tmpData['passenger_id'] = $passenger['passenger_id'];
            $tmpData['prize'] = $passenger['prize'];
            // $tmpData['tag']             = Arrays::value($passenger, 'tag');
            $res = self::save($tmpData);
            //座位绑定
        }
        return $res;
    }

    /**
     * 20230303:适用于每个用户价格一致的情况
     * 获取的数组，再使用orderPassengerAdd进行添加（看似多一步，但是扩展性好）
     * @param type $orderId 订单号
     * @param type $psgIds  乘客数组
     * @param type $prize   单价
     */
    public static function getPassengerArr($orderId, $psgIds, $prize) {
        $passenger = [];
        foreach ($psgIds as $psgId) {
            $data = UserPassengerService::getInstance($psgId)->get();
            $tmp = [];
            $tmp['realname'] = $data['realname'];
            $tmp['id_no'] = $data['id_no'];
            $tmp['phone'] = $data['phone'];
            $tmp['order_id'] = $orderId;
            $tmp['passenger_id'] = $data['id'];
            $tmp['prize'] = $prize;

            $passenger[] = $tmp;
        }
        return $passenger;
    }

    /**
     * 20230304订单团价格
     * @param type $orderId
     * @return type
     */
    public static function orderTourPrize($orderId) {
        $tourPassengers = OrderService::getInstance($orderId)->objAttrsList('tourPassengers');
        return array_sum(array_column($tourPassengers, 'prize'));
    }

    /**
     * 钩子-保存前
     */
    public static function ramPreSave(&$data, $uuid) {
        
    }

    /**
     * 钩子-保存后
     */
    public static function ramAfterSave(&$data, $uuid) {
        
    }

    /**
     * 钩子-更新前
     */
    public static function ramPreUpdate(&$data, $uuid) {
        
    }

    /**
     * 钩子-更新后
     */
    public static function ramAfterUpdate(&$data, $uuid) {
        
    }

    /**
     * 钩子-删除前
     */
    public function ramPreDelete() {
        
    }

    /**
     * 20230323：单个的退票逻辑
     */
    public function ref() {
        self::checkTransaction();
        $info = $this->get();
        // 客人的状态更新一下
        // 钱退给用户
    }

    /**
     * 20230323:提取订单的乘客数量
     */
    public static function orderPassengerCount($orderId) {
        $con[] = ['order_id', '=', $orderId];
        $con[] = ['is_ref', '=', 0];
        return self::where($con)->count();
    }

    /**
     * 钩子-删除后
     */
    public function ramAfterDelete() {
        
    }

    /**
     * 20230318:团客核销
     * @param type $id
     * @param type $param
     */
    public static function ticketCheck($id) {
        $info = self::getInstance($id)->get();
        if (!$info['is_ref']) {
            throw new Exception('票已退不可核销,流水号:' . $info['id']);
        }
        if (!$info['is_pay']) {
            throw new Exception('订单未支付不可核销');
        }
        if ($info['is_ticked']) {
            throw new Exception('该票已核销：' . $info['realname']);
        }

        $data['is_ticked'] = 1;
        $res = self::getInstance($id)->update($data);
        return $res;
    }

    /**
     *
     */
    public function fCompanyId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建时间
     */
    public function fCreateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建者，user表
     */
    public function fCreater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 有使用(0否,1是)
     */
    public function fHasUsed() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 【冗】身份证号
     */
    public function fIdNo() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未删，1：已删）
     */
    public function fIsDelete() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未锁，1：已锁）
     */
    public function fIsLock() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 乘客，逗号隔
     */
    public function fPassengerId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 【冗】手机号码
     */
    public function fPhone() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 【冗】姓名
     */
    public function fRealname() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 备注
     */
    public function fRemark() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [20220427]学校号数
     */
    public function fSchoolNo() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 排序
     */
    public function fSort() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 状态(0禁用,1启用)
     */
    public function fStatus() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 订单表id
     */
    public function fTourTimeId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新时间
     */
    public function fUpdateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新者，user表
     */
    public function fUpdater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

}
