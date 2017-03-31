<?php
namespace Jkd\Traits;
use Illuminate\Support\Facades\DB;

/**
 * 采集的复用
 */
trait ModelCaiji
{
    /**
     * 保存采集的数据方法
     * 
     * @param array $list 列表页数据
     * @param array $content 内容页数据
     * @example $list = [
     *     "typeid" => 3,
     *     "title" => "标题",
     *     "shorttitle" => "副标题(非必须)",
     *     "writer" => "pvp(非必须)",
     *     "litpic" => "图片的URL地址",
     *     "keywords" => "关键词 多个之间用逗号“,”隔开(非必须)"，
     *     "filename" => "文件名(非必须)"
     *     "created_at" => "时间 格式如“2017-03-07 10:56:22”(非必须)"
     * ]
     * @example $content = [
     *     "aid" => "文章ID，只添加内容，不添加列表数据时才需要传递(非必须)"
     *     "body" => "内容",
     *     "source" => "来源，如视频来源(非必须)",
     * ]
     * 
     * <pre>
     * 
     *  // 添加列表和内容
     *   $this->save([
     *       'typeid' => 33, // 联赛视频分类
     *       'title' => 'TEST',
     *       'litpic' => 'http://www.baidu.com',
     *       'keywords' => 'test',
     *       'description' => 'description',
     *       'filename' => 'a.html',
     *       'created_at' => date('Y-m-d H:i:s'),
     *   ], [
     *       'body' => 'test test test'
     *   ]);
     *   // 只添加列表
     *   $this->save([
     *       'typeid' => 33, // 联赛视频分类
     *       'title' => 'TEST',
     *      'litpic' => 'http://www.baidu.com',
     *      'keywords' => 'test',
     *       'description' => 'description',
     *      'filename' => 'a.html',
     *      'created_at' => date('Y-m-d H:i:s'),
     *  ]);
     *  // 只添加内容
     *   $this->save([
     *   ], [
     *       'aid' => 12615,
     *       'typeid' => 33,
     *       'body' => 'test test test'
     *   ]);
     * </pre>
     */
    public function save($list = array(), $content = array())
    {
        $haveList = !empty($list);
        $haveContent = !empty($content);
        
        /* 合并指定的和列表默认的值 */
        if ($haveList) {
            /* 列表默认值 */
            $defaultList = [
                'typeid' => 0, // 分类
                'channel' => 1,
                'show' => 1,
                'flag' => 0,
                'ismake' => 0,
                'click' => rand(100, 9999),
                'title' => '',
                'shorttitle' => '',
                'writer' => '',
                'arcrank' => 0, // 测试环境默认0正常  -1为待审核
                'litpic' => '',
                'keywords' => '',
                'description' => '',
                'filename' => '',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $list = array_merge($defaultList, $list);
        }
        
        /* 合并指定的和内容默认的值 */
        if ($haveContent) {
            $defaultContent = [
                'aid' => 0,
                'typeid' => isset($list['typeid']) ? $list['typeid'] : 0,
                'body' => '',
                'source' => '',
                'templet' => '',
                'created_at' => isset($list['created_at']) ? $list['created_at'] : date('Y-m-d H:i:s'),
            ];
            $content = array_merge($defaultContent, $content);
        }
        
        /* 用事务执行数据库表插入操作 */
        DB::beginTransaction();
        try {
            // 添加列表和数据
            if ($haveList) {
                $id = DB::table('archives')->insertGetId($list);
                if ($id && $haveContent) {
                    $content['aid'] = $id;
                    DB::table('addonarticles')->insert($content);
                }
                DB::commit();
                return $id;
            } 
            // 只添加内容数据
            elseif ($haveContent && isset($content['aid'])) {
                DB::table('addonarticles')->insert($content);
                DB::commit();
            }
        } catch (Exception $e){
            DB::rollback();
        }
    }
    
    /**
     * 查询列表数据
     * 
     * @param int $typeid 分类
     * @param int $offset 查询偏移量
     * @param int $limit 查询限制数
     */
    public function query($typeid = 0, $offset = 0, $limit = 100)
    {
        $query = DB::select('SELECT * FROM archives WHERE typeid=:typeid LIMIT :offset,:limit', [
            'typeid' => $typeid,
            'offset' => $offset, 
            'limit' => $limit
        ]);
        return $query;
    }
    
}
