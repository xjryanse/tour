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
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;


    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\tour\\model\\TourGroup';

    use \xjryanse\tour\service\group\PaginateTraits;
    use \xjryanse\tour\service\group\FieldTraits;
    
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
     * 20230324
     * @return type
     */
    public function getCateDesc() {
        $info = $this->get();
        return TourTypeService::getCateDesc($info['tour_type']);
    }

    /**
     * 类型，提取id
     * @param type $cate
     * @return type
     */
    public static function cateGroupIds($cate){
        $tourTypes  = TourTypeService::cateTourTypeColumn($cate);
        $con    = [];
        $con[]  = ['tour_type','in',$tourTypes];
        $ids = self::where($con)->column('id');
        return $ids;
    }

}
