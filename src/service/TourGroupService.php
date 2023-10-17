<?php

namespace xjryanse\tour\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\user\service\UserAuthUserRoleService;

/**
 * 
 */
class TourGroupService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\tour\\model\\TourGroup';

    public static function extraDataAuthCond() {
        $userId = session(SESSION_USER_ID);
        $userRoleKey = UserAuthUserRoleService::userRoleKeyForDataAuth($userId);
        $con = [];
        // 20230323：客户权限添加过滤条件
        if ($userRoleKey == 'customer') {
            $con[] = ['user_id', '=', $userId];
        }
        return $con;
        //客户权限，customer_id = ''
    }

    public static function extraPreSave(&$data, $uuid) {
        if (!Arrays::value($data, 'user_id')) {
            $data['user_id'] = session(SESSION_USER_ID);
        }
    }

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    $timeCounts = TourTimeService::groupBatchCount('group_id', $ids);
                    $conOnsale[] = ['finalTime', '>=', date('Y-m-d H:i:s')];
                    //20230324：在售团次
                    $onsaleTimeCounts = TourTimeService::groupBatchCount('group_id', $ids, $conOnsale);
                    $tplPassengerCounts = TourPassengerTplService::groupBatchCount('group_id', $ids);

                    foreach ($lists as &$v) {
                        // 在售团次数
                        $v['onsaleTimeCounts'] = Arrays::value($onsaleTimeCounts, $v['id'], 0);
                        // 团次数
                        $v['timeCounts'] = Arrays::value($timeCounts, $v['id'], 0);
                        // 20230319:有否团次，用于前端判断展示
                        $v['hasTime'] = $v['timeCounts'] ? 1 : 0;
                        //模板旅客数
                        $v['tplPassengerCounts'] = Arrays::value($tplPassengerCounts, $v['id'], 0);
                    }

                    return $lists;
                });
    }

    /**
     * 20230317:线路团次
     * @param type $con
     * @return type
     */
    public static function paginateForWeb($con = []) {
        $con[] = ['company_id', '=', session(SESSION_COMPANY_ID)];
        $con[] = ['status', '=', 1];
        $lists = self::paginate($con, 'status desc,sort', 1000);

        $conTime[] = ['company_id', 'in', session(SESSION_COMPANY_ID)];
        $conTime[] = ['group_id', 'in', array_column($lists['data'], 'id')];
        //前后60天
        $conTime[] = ['tour_time', '>=', date('Y-m-d 00:00:00', strtotime('-60 days'))];
        $conTime[] = ['status', '=', 1];
        $tourTimeIds = TourTimeService::ids($conTime, 'tour_time desc');
        $tourTimeList = TourTimeService::extraDetails($tourTimeIds);

        foreach ($lists['data'] as &$v) {
            // 拼接班次
            foreach ($tourTimeList as &$v2) {
                if ($v2['group_id'] == $v['id']) {
                    $v['tourTimes'][] = $v2;
                }
            }

            $conG = [];
            $conG[] = ['canRegist', '=', 1];
            $arr = Arrays2d::listFilter($v['tourTimes'], $conG);
            $count = count($arr);
            $sum = array_sum(array_column($arr, 'passengerCounts'));
            // 20230320
            $v['timeDesc'] = $count ? '报名中:' . $count . '期，已报名' . $sum . '人' : '';
        }

        return $lists;
    }

    /**
     * 20230323:微信小程序的线路团次
     * @param type $con
     * @return type
     */
    public static function paginateForWeapp($con = []) {
        $con[] = ['status', '=', 1];
        $lists = self::paginateRaw($con, 'status desc,sort', 1000);
        foreach ($lists['data'] as &$v) {
            // 有在售，排前面
            $v['sort'] = $v['timeCounts'] ? $v['sort'] : $v['sort'] * 1000;
        }
        $lists['data'] = Arrays2d::sort($lists['data'], 'sort');

        return $lists;
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
     * 20230324
     * @return type
     */
    public function getCateDesc() {
        $info = $this->get();
        return TourTypeService::getCateDesc($info['tour_type']);
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
     * 线路详情
     */
    public function fGroupDetail() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 线路图片
     */
    public function fGroupImg() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 团名
     */
    public function fGroupName() {
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
     * 是否置顶：0否，1是
     */
    public function fIsTop() {
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
     * 团类型：旅游团；学校团
     */
    public function fTourType() {
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

}
