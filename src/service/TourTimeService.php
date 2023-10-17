<?php

namespace xjryanse\tour\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\order\service\OrderService;
use xjryanse\user\service\UserAuthUserRoleService;
use xjryanse\logic\Arrays;
use Exception;

/**
 * 
 */
class TourTimeService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelQueryTrait;

// 2022-11-20 TODO 提升性能，新增/修改时需要进行更新
    use \xjryanse\traits\StaticModelTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\tour\\model\\TourTime';

    /**
     * 20230324
     * @return type
     */
    public static function extraDataAuthCond() {
        $userId = session(SESSION_USER_ID);
        $userRoleKey = UserAuthUserRoleService::userRoleKeyForDataAuth($userId);
        $conG = [];
        // 20230323：客户权限添加过滤条件
        if ($userRoleKey == 'customer') {
            $conG[] = ['user_id', '=', $userId];
        }

        $con = [];
        // 如果有组条件，则提取组id
        if ($conG) {
            // 提取客户所拥有的group
            $groupIds = TourGroupService::where($conG)->column('id');
            $con[] = ['group_id', 'in', $groupIds];
        }
        return $con;
        //客户权限，customer_id = ''
    }

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    $conTP[] = ['is_ref', '=', 0];
                    $passengerCounts = TourPassengerService::groupBatchCount('tour_time_id', $ids, $conTP);
                    // 总订单数
                    $orderCounts = OrderService::groupBatchCount('tour_time_id', $ids);
                    // 有效订单数
                    $con[] = ['is_cancel', '=', '0'];
                    $effOrderCounts = OrderService::groupBatchCount('tour_time_id', $ids, $con);
                    // 付款统计
                    $effPayCounts = OrderService::groupBatchSum('tour_time_id', $ids, 'pay_prize', $con);
                    // 退款统计
                    $effRefCounts = OrderService::groupBatchSum('tour_time_id', $ids, 'refund_prize', $con);

                    foreach ($lists as &$v) {
                        $v['effMoney'] = Arrays::value($effPayCounts, $v['id'], 0) - Arrays::value($effRefCounts, $v['id'], 0);
                        $v['registFinalTime'] = self::getInstance($v['id'])->calFinalTime();
                        // 20230321：是否可以报名
                        $v['canRegist'] = $v['registFinalTime'] > date('Y-m-d H:i:s') ? 1 : 0;
                        // 报名时段
                        $v['registTimeStr'] = self::getInstance($v['id'])->getRegistTimeStr();
                        // 培训时段
                        $v['peiTimeStr'] = self::getInstance($v['id'])->getPeiTimeStr();
                        // 发团时段
                        $v['tourTimeStr'] = self::getInstance($v['id'])->getTourTimeStr();
                        // 发团时段：简短
                        $v['tourTimeStrShort'] = self::getInstance($v['id'])->getTourTimeStr('m-d H:i');
                        // 订单数
                        $v['orderCounts'] = Arrays::value($orderCounts, $v['id'], 0);
                        // 有效订单数
                        $v['effOrderCounts'] = Arrays::value($effOrderCounts, $v['id'], 0);
                        //旅客数
                        $v['passengerCounts'] = Arrays::value($passengerCounts, $v['id'], 0);
                        // 剩余人数
                        $v['remainCounts'] = $v['plan_passengers'] - $v['passengerCounts'];
                        //发团状态
                        $v['tourStatus'] = $v['tour_time'] > date('Y-m-d H:i:s') ? 'todo' : 'finish';
                    }

                    return $lists;
                });
    }

    public static function extraPreSave(&$data, $uuid) {
        if (!Arrays::value($data, 'time_name')) {
            $data['time_name'] = date('Y-m-d H:i', strtotime($data['tour_time']))
                    . TourGroupService::getInstance($data['group_id'])->getCateDesc();
        }
        $data['finalTime'] = self::doCalFinalTime($data);
    }

    /**
     * 20230324
     * @param array $data
     * @param type $uuid
     */
    public static function extraPreUpdate(&$data, $uuid) {
        $info = self::getInstance($uuid)->get();
        $data['finalTime'] = self::doCalFinalTime(array_merge($info, $data));
    }

    public function extraPreDelete() {
        $info = $this->info();
        if ($info['orderCounts']) {
            throw new Exception('有下单记录不可删');
        }
    }

    /**
     * 20230321计算最终可报名时间
     */
    public function calFinalTime() {
        $info = $this->get();
        return self::doCalFinalTime($info);
    }

    /**
     * 20230324:拆逻辑
     * @param type $info
     * @return type
     */
    protected static function doCalFinalTime($info) {
        // 报名截止时间
        $registEndTime = Arrays::value($info, 'regist_end_time');
        // 开始培训时间
        $peiStartTime = Arrays::value($info, 'pei_start_time');
        // 发团时间
        $tourTime = Arrays::value($info, 'tour_time');

        $finalTime = $registEndTime ?:
                ($peiStartTime ?:
                ($tourTime ?: ''));
        return $finalTime;
    }

    /**
     * 20230320 拼接登记时段字符串
     */
    public function getRegistTimeStr() {
        $info = $this->get();
        $str = '';

        $finalTime = $this->calFinalTime();
        if ($finalTime < date('Y-m-d h:i:s')) {
            $str = '已截止';
        } else {
            $str .= $info['regist_start_time'] ? date('Y-m-d', strtotime($info['regist_start_time'])) : '即日起';
            $str .= ' 至 ';
            $str .= date('Y-m-d', strtotime($finalTime));
        }
        return $str;
    }

    /*
     * 获取培训时段数组
     */

    public function getPeiTimeStr() {
        $info = $this->get();
        $str = '';
        if ($info['pei_start_time'] && $info['pei_end_time']) {
            $str .= date('Y-m-d', strtotime($info['pei_start_time']));
            $str .= ' 至 ';
            $str .= date('Y-m-d', strtotime($info['pei_end_time']));
        }
        return $str;
    }

    /**
     * 20230325:发团时段
     * @return type
     */
    public function getTourTimeStr($format = 'Y-m-d H:i') {
        $info = $this->get();
        $str = '';
        if ($info['tour_time']) {
            $str .= date($format, strtotime($info['tour_time']));
        }
        if ($info['tour_end_time']) {
            if ($info['tour_time']) {
                $str .= ' 至 ';
            }
            $str .= date($format, strtotime($info['tour_end_time']));
        }
        return $str;
    }

    /**
     * 2023-03-03
     * @param type $number      本次下单人数
     * @throws Exception
     */
    public function canOrder($number) {
        // 校验下单时间：
        $this->orderTimeCheck();
        // 校验是否足额
        $remainSeats = $this->remainSeats();
        if ($remainSeats < $number) {
            throw new Exception('名额不足，剩余' . $remainSeats);
        }
    }

    /**
     * 2023-03-03：校验订单的下单时间
     * @throws Exception
     */
    public function orderTimeCheck() {
        $info = $this->staticGet();
        // 判断报名开始时间
        $startTime = $info['regist_start_time'];
        if ($startTime && date('Y-m-d H:i:s') < $startTime) {
            $timeDesc = date('Y-m-d H:i', strtotime($startTime));
            throw new Exception('报名开始时间' . $timeDesc . '未到');
        }
        // 判断报名截止时间
        // $endTime = $info['regist_end_time'];
        $finalTime = $this->calFinalTime();
        if ($finalTime && date('Y-m-d H:i:s') > $finalTime) {
            $timeDesc = date('Y-m-d H:i', strtotime($finalTime));
            throw new Exception('报名截止时间' . $timeDesc . '已过');
        }
    }

    /**
     * 2023-03-03:剩余席位
     * @return int
     */
    public function remainSeats() {
        $info = $this->staticGet();
        // 有开启锁购票，直接返回无票
        // 设置的售票座位数
        $allSeats = $info['plan_passengers'];
        $cond[] = ['tour_time_id', 'in', $this->uuid];
        $cond[] = ['is_ref', '=', 0];
        $saleSeats = TourPassengerService::where($cond)->count();
        $remainSeats = $allSeats - $saleSeats;
        return $remainSeats > 0 ? $remainSeats : 0;
    }

    /*
     * 20230305:前端小程序用户下单的列表查询，带上了isSelected
     */

    public static function userOrderPaginate($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        $con[] = ['finalTime', '>=', date('Y-m-d H:i:s')];
        $conAll = array_merge($con, self::commCondition());
        $res = self::paginateRaw($conAll, 'tour_time', $perPage, $having, $field, $withSum);
        foreach ($res['data'] as $k => &$v) {
            if (!$v['canRegist']) {
                // 20230322:已截止的，清空人数（兼容前端页面显示）
                $v['remainCounts'] = 0;
            }
            // 20230306首个默认选中
            $v['isSelected'] = $k == 0 && $v['remainCounts'] ? true : false;
        }

        return $res;
        // return self::commPaginate($con, $order, $perPage, $having, $field);
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
     * 钩子-删除后
     */
    public function ramAfterDelete() {
        
    }

    /**
     * 报团价（20230304使用中）
     */
    public function fTimePrize() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 公司
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
     * 发布客户
     */
    public function fCustomerId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 团分组id
     */
    public function fGroupId() {
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
     * 备注
     */
    public function fRemark() {
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
     * 团次名
     */
    public function fTimeName() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 出团时间
     */
    public function fTourTime() {
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

    /**
     * 发布用户
     */
    public function fUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    public function fLockRef() {
        return $this->getFFieldValue(__FUNCTION__);
    }

}
