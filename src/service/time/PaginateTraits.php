<?php
namespace xjryanse\tour\service\time;

use xjryanse\tour\service\TourTypeService;
use xjryanse\tour\service\TourGroupService;
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
        
        $groupIds = TourGroupService::cateGroupIds($cate);
        $con[] = ['group_id','in',$groupIds];

        return self::paginateX($con, $order, $perPage, $having, $field, $withSum);
    }

}
