<?php
namespace xjryanse\tour\model;

/**
 * 
 */
class TourGroup extends Base
{
    public static $picFields = ['group_img','poster_img'];

    public function getGroupImgAttr($value) {
        return self::getImgVal($value);
    }
    /**
     * 图片修改器，图片带id只取id
     * @param type $value
     * @throws \Exception
     */
    public function setGroupImgAttr($value) {
        return self::setImgVal($value);
    }
    
    public function getPosterImgAttr($value) {
        return self::getImgVal($value);
    }

    /**
     * 图片修改器，图片带id只取id
     * @param type $value
     * @throws \Exception
     */
    public function setPosterImgAttr($value) {
        return self::setImgVal($value);
    }
    
    /**
     * 模板
     * @param type $value
     * @return type
     */
    public function getTemplateAttr($value) {
        return self::getImgVal($value);
    }

    /**
     * 模板
     * @param type $value
     * @throws \Exception
     */
    public function setTemplateAttr($value) {
        return self::setImgVal($value);
    }
}