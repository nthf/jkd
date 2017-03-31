<?php

namespace Jkd\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Archive extends Model
{
    //protected $table = "archives";
    //protected $relations = ['detail'];
    protected $fillable = ['typeid','channel','show','flag','ismake','click','title','shorttitle','writer','arcrank','litpic','keywords','description','filename','deletea_at','created_at','updated_at'];

    /**
     * 文章关联关系
     */
    public function detail()
    {
        return $this->hasOne(Addonarticle::class, 'aid',  'id');
    }


    /**
     *  与采集临时表关联
     */
    public function addontmp()
    {
        return $this->hasOne(Addontmp::class,'aid','id');

    }

    /**
     * 标签关联关系
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }
    
    /**
     * 获取文章列表
     * 
     * @param int $channel 频道（1：文章，2：专题）
     * @param int $typeid 分类ID （默认为null）
     * @param string $flag 推荐位置 （默认为null）
     * @param int $offset 查询的偏移量
     * @param int $limit 查询的限制数
     * @return array
     */
    public static function getList($channel, $typeid = null, $flag = null, $offset = 0, $limit = 0)
    {
        $query = array();
        
        do {
            /* 判断参数类型 */
            if (!is_numeric($channel) || !is_numeric($offset) || !is_numeric($limit)) {
                break;
            }
             
            /* 场景1： 取固定推荐位置的文章列表 */
            if ($flag !== null && in_array($flag, range('a', 'z')) ) {
                //$query = DB::select('SELECT * FROM archives WHERE arcrank=0 AND FIND_IN_SET(:flag, flag) ORDER BY id DESC', ['flag' => $flag]);
                $query = Archive::whereRaw('arcrank=0 AND FIND_IN_SET("' . $flag .'",flag)')->orderBy('id', 'desc');
                break;
            }
            
            /* 场景2： 取某频道的最新文章列表 */
            if ($typeid === null || !is_numeric($typeid)) {
                $query = Archive::where([ ['channel', '=', $channel], ['arcrank', '=', 0] ])->orderBy('id', 'desc');
                break;
            }
            
            /* 场景3： 取某频道某分类下的最新文章列表 */
            if (is_numeric($typeid)) {
                $query = Archive::where([ ['typeid', '=', $typeid], ['channel', '=', $channel], ['arcrank', '=', 0] ])->orderBy('id', 'desc');
                break;
            }
        }
        while (false);

        if (!empty($query)) {
            // 限制返回的结果个数
            if ($limit !== 0) {
                $query = $query->offset($offset)->limit($limit)->get();
            } 
            // 自动分页
            else {
                $query = $query->paginate(20);
            }
            
            // 封装返回的结果集
            foreach ($query as &$value) {
                if (empty($value->litpic)) {
                    // 默认
                    $value->litpic = url(config('admin.upload.host') . 'image/300x150.jpg');
                } elseif (0 !== strpos($value->litpic, 'http') ) {
                    // 访问URL
                    $value->litpic = url(config('admin.upload.host') . $value->litpic);
                }
                $value->link = url('/detail/' . $value['id'] . '.html');
                $value->time = date('Y-m-d', strtotime($value['created_at']));
            }
        }
        
        return $query;
    }
    
    /**
     * 根据路径获取指定的栏目
     * 
     * @param string $path 访问路径
     * @param int $offset 查询偏移量
     * @param int $limit 查询限制数
     * @param mixed $lanmuInfo 栏目信息
     * @return object | null
     */
    public static function getLiebiaoByPath($path, $offset, $limit, &$lanmuInfo = null)
    {
        $liebiao = null;
        
        do {
            /* 查询栏目列表 */
            $lanmuList = DB::select('SELECT * FROM arctypes WHERE channel IN(1,2)');
            if (empty($lanmuList)) {
                break;
            }
            
            /* 遍历查找相同的路径 */
            $lanmuArr = array();
            foreach ($lanmuList as $value) {
                if (trim($path, '/') === trim($value->paths, '/')) {
                    $lanmuInfo = $value;
                    if ($value->top_id == 0) {
                        break;
                    }
                }
                $lanmuArr[ $value->id ] = $value;
            }
            $lanmuList = null; unset($lanmuList);
            
            /* 判断是否有对应的栏目 */
            if (empty($lanmuInfo)) {
                break;
            }
            
            /* 栏目确定是聚合栏目, 通过标题模糊查询栏目关键词 */
            if ($lanmuInfo->channel == 2) {
                $keywordList = empty($lanmuInfo->alias) ? array($lanmuInfo->type_name) : explode(',', $lanmuInfo->alias);
                $sql = '';
                foreach ($keywordList as $key => $value) {
                    $value = trim($value, ',');
                    if ($value !== '') {
                        $sql .= "title LIKE \"%{$value}%\" OR ";
                    }
                }
                $sql = rtrim($sql, 'OR ');
                $sql = 'arcrank=0 AND ' . ($key > 0 ? '(' . $sql . ')' : $sql);
                $liebiao = DB::table('archives')->whereRaw($sql)->orderBy('id', 'DESC')->offset($offset)->limit($limit)->get();
                break;
            }
            
            /* 栏目是视频栏目, 查找所有的视频 */
            if ($lanmuInfo->id == 2) {
                $liebiao = DB::table('archives')->whereRaw('`show`=3 AND arcrank=0')->orderBy('id', 'DESC')->offset($offset)->limit($limit)->get();
                break;
            }
            
            /* 栏目确定是是文章栏目, 通过栏目ID查询 */
            $lanmuChilds = DB::select('SELECT id,type_name,paths FROM arctypes WHERE top_id=:top_id', ['top_id' => $lanmuInfo->id]);
            // 情况1. 有子栏目
            if (!empty($lanmuChilds)) {
                $childIdList = array();
                $childIdList[] = $lanmuInfo->id;
                foreach ($lanmuChilds as $value) {
                    $childIdList[] = $value->id;
                }
                $sql = 'typeid IN (' . implode(',', $childIdList) . ') AND arcrank=0';
                $liebiao = DB::table('archives')->whereRaw($sql)->orderBy('id', 'DESC')->offset($offset)->limit($limit)->get();
                break;
            }
            // 情况2. 没有子栏目
            $liebiao = DB::table('archives')->whereRaw('typeid=:typeid AND arcrank=0', ['typeid' => $lanmuInfo->id])->orderBy('id', 'DESC')->offset($offset)->limit($limit)->get();
        }
        while (false);
        
        return $liebiao;
    }
    
}
