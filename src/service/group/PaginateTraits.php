<?php
namespace xjryanse\tour\service\group;

use xjryanse\tour\service\TourTypeService;
use xjryanse\logic\Arrays2d;
use think\facade\Request;
/**
 * 分页复用列表
 */
trait PaginateTraits{
    
    /**
     * 20230323:带类型提取分页,后台用
     * @param type $con
     * @return type
     */
    public static function paginateForAdminWithCate($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        $cate       = Request::param('tourCate');
        $tourTypes  = TourTypeService::cateTourTypeColumn($cate);
        $con[] = ['tour_type','in',$tourTypes];

        return self::paginateX($con, $order, $perPage, $having, $field, $withSum);
    }
    
    /**
     * 20230323:带类型提取分页
     * @param type $con
     * @return type
     */
    public static function paginateForWeappWithCate($con = []) {
        $cate       = Request::param('tourCate');
        $tourTypes  = TourTypeService::cateTourTypeColumn($cate);
        $con[] = ['tour_type','in',$tourTypes];

        return self::paginateForWeapp($con);
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

}
