<?php

namespace Jkd\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 武器相关的模型
 */
class Wuqi extends Model
{
    /**
     * 获取武器列表
     * 
     * @param mixed (null | int | array) $type
     * @return mixed (null | object)
     */
    public static function getList($type = null) 
    {
        $list = null;
        
        /* 所有的(默认首页) */
        if (null === $type) {
            $list = Wuqi::all();
            $list = self::genGroup($list);
        }
        /* 主武器 */
        elseif (is_array($type)) {
            $typeIds = implode(',', $type);
            $list = Wuqi::whereRaw("type IN ({$typeIds})")->get();
            $list = self::genGroup($list);
        }
        /* 其他武器 */
        else {
            $list = Wuqi::where('type', $type)->get();
            $list = array($type => $list);
        }
        
        return $list;
    }
    
    /**
     * 按武器类型进行分组
     * 
     * @param object $list
     * @return object
     */
    private static function genGroup($list)
    {
        $group = array();
        
        foreach ($list as $value) {
            $group[$value->type][] = $value;
        }
        
        return $group;
    }
    
}
