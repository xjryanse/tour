<?php

namespace xjryanse\tour\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;

/**
 * 
 */
class TourTypeService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;


    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\tour\\model\\TourType';

    use \xjryanse\tour\service\type\FieldTraits;
    use \xjryanse\tour\service\type\TriggerTraits;
    
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    $groupCounts = TourGroupService::groupBatchCount('tour_type', array_column($lists, 'tour_type'));

                    foreach ($lists as &$v) {
                        //分组
                        $v['groupCount'] = Arrays::value($groupCounts, $v['tour_type'], 0);
                    }

                    return $lists;
                });
    }

    /**
     * 20230324:获取证书的描述
     */
    public static function getCateDesc($key) {
        $con[] = ['tour_type', '=', $key];
        $info = self::where($con)->find();
        $arr['cert'] = '考试';
        $arr['travel'] = '出团';

        return Arrays::value($arr, $info['cate']);
    }

    /**
     * 分类提取团类型
     * @createTime 2023-10-17
     * @param type $cate
     * @return type
     */
    public static function cateTourTypeColumn($cate){
        $con[] = ['cate','=',$cate];
        return self::where($con)->column('tour_type');
    }


}
