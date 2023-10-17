<?php
namespace xjryanse\tour\model;

/**
 * 
 */
class TourTime extends Base
{
    
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