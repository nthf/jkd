<?php

namespace Jkd\Models;

use Jkd\Admin\Extensions\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;

/**
 * 分类的树形结构
 *
 * @property int $id
 * @method where($parent_id, $id)
 */
class Arctype extends Model
{
    use ModelTree, AdminBuilder;

    protected $table = 'arctypes';
    protected $fillable = ['id', 'type_name', 'top_id','channel'];

    private static $channel;

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setParentColumn('top_id');
        $this->setTitleColumn('type_name');
        $this->setOrderColumn('order');
    }

    /**
     * Get options for Select field in form.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function selectOptions()
    {
        $options = (new static())->withQuery(function ($model){
           return $model->where('channel', self::$channel);
        })->buildSelectOptions();

        return collect($options)->prepend('请选择分类', 0)->all();
    }
    
    /**
     * 提供给专题页使用的
     * 
     * 备注：只有二级，三级已过滤，此结构有缺陷
     * 
     * Get options for Select field in form.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function selectOptionsForTopic()
    {
        $options = array('' => array(0 => str_repeat('&nbsp;', 5) . '请选择分类'));
        $topIds = array();
        $build = (new static())->withQuery(function ($model){
           return $model->where('channel', self::$channel);
        })->buildSelectOptionsForTopic([], 0, '', $topIds);

        foreach ($build as $id => $value) {
            // 只返回二级的列表
            if (isset($topIds[$id])) {
                $options[ $topIds[$id] ] = $value;
            }
        }
        $build = array(); unset($build);

        return $options;
    }

    /**
     * 提供给专题页使用的
     * 
     * Build options of select field in form.
     * 
     * @param array  $nodes
     * @param int    $parentId
     * @param string $prefix
     * @param array $topIds
     * @return array
     */
    protected function buildSelectOptionsForTopic(array $nodes = [], $parentId = 0, $prefix = '', &$topIds = array())
    {
        $prefix = $prefix ?: str_repeat('&nbsp;', 6);

        $options = [];

        if (empty($nodes)) {
            $nodes = $this->allNodes();
        }

        foreach ($nodes as $node) {
            $node[$this->titleColumn] = $prefix.'&nbsp;'.$node[$this->titleColumn];
            if ($node[$this->parentColumn] == $parentId) {
                $children = $this->buildSelectOptionsForTopic($nodes, $node[$this->getKeyName()], $prefix.$prefix, $topIds);
                
                if ($node[$this->parentColumn] != 0) {
                    // 构建二维数组, 父类=>array(1 => '子类1', 2 => '子类2')
                    $options[$node[$this->parentColumn]][$node[$this->getKeyName()]] = $node[$this->titleColumn];
                } else {
                    $topIds[ $node[$this->getKeyName()] ] = $node[$this->titleColumn];
                }
                
                if ($children) {
                    $options += $children;
                }
            }
        }

        return $options;
    }

    /**
     * 设置频道
     * 
     * @param int $channel 频道(1:文章,2:专题)
     * @return void
     */
    public static function setChannel($channel)
    {
        self::$channel = $channel;
    }
    
    /**
     * 获取指定栏目
     * 
     * @param int $channel (1:文章,2:专题,3:导航)
     * @return object | null
     */
    public static function getTypeList($channel, $topId = 0, $find = null)
    {
        $typeList = null;
        
        do {
            if (!is_numeric($channel) || !is_numeric($topId)) {
                break;
            }
            
            /* 通过ID查找 */
            if (is_numeric($find)) {
                $typeList = Arctype::find($find);
                break;
            }
            
            /* 查找所有的栏目 */
            $typeAll = Arctype::where([ ['channel', '=', $channel], ['top_id', '=', $topId] ])->get();
            if (empty($typeAll)) {
                break;
            }
            
            /* 返回所有的子栏目 */
            if (null === $find) {
                $typeList = $typeAll;
                break;
            }
            
            /* 遍历，找到则返回 */
            foreach ($typeAll as $value) {
                if ($find == $value->type_name || $find == $value->alias) {
                    $typeList = $value;
                    break;
                }
            }
            $typeAll = null;
        }
        while (false);
        
        return $typeList;
    }

}
