<?php

namespace Jkd\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;

class TemplateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

        /*
         * 随机调取内容
         *
         *
         *
         * **/
        Blade::directive('suiji', function($args){
            $query = null;
            do {
                /* 处理接收的参数 */
                $args = explode(',', $args);
                if (count($args) < 3) {
                    break;
                }


                if ($args[0] === '标签') {
                    $renderType = '"tag"';
                } else {
                    $renderType = '"liebiao"';
                }

                $preStr = "<?php ob_start(); \$renderType = {$renderType};";

                /* 所有 */
                if ($args[0] === '所有') {
                    return $preStr . " \$suiji = getRandom('archives',['arcrank'=>'0'],{$args[1]},{$args[2]}); ?>";
                    break;
                }

                /* 视频 */
                if ($args[0] === '视频') {
                    return $preStr . " \$suiji = getRandom('archives',['show'=>'3','arcrank'=>'0'],{$args[1]},{$args[2]}); ?>";
                    break;
                }

                /* 图片 */
                if ($args[0] === '图片') {
                    return $preStr . " \$suiji = getRandom('archives',['show'=>'2','arcrank'=>'0'],{$args[1]},{$args[2]}); ?>";
                    break;
                }

                /* 文章 */
                if ($args[0] === '文章') {
                    return $preStr . " \$suiji = getRandom('archives',['show'=>'1','arcrank'=>'0'],{$args[1]},{$args[2]}); ?>";
                    break;
                }

                /* 标签 */
                if ($args[0] === '标签') {
                    return $preStr . " \$suiji = getRandom('tags',[],{$args[1]},{$args[2]}); ?>";
                    break;
                }

            }
            while (false);

        });
        Blade::directive('endsuiji', function() {
            return '<?php $html = ob_get_contents(); $html = renderTpl($suiji,$html,$renderType); ob_end_clean(); echo $html; ?>';
        });

        /**
         * 内容列表的标签
         *
         * @param string $args[0] "分类名称"
         * @param int $args[1] 查询的偏移量
         * @param int $args[2] 查询的限制数
         * @param int $args[3] 显示的模版名称
         * @example 在Blade模版中使用如下示例
         * <pre>
         *    @liebiao(1,0,10)
         *    @liebiao(资讯,0,10,news)
         *    @liebiao(资讯,0,10,news2)
         *    @liebiao(资讯,0,10,news3)
         *    @liebiao(视频,0,10,video)
         *    @liebiao(壁纸,0,10,wallpaper)
         *    @liebiao(14|15|16,0,10,news)
         * </pre>
         */
        Blade::directive('liebiao', function($args) {
            $query = null;
            
            do {
                /* 处理接收的参数 */
                $args = explode(',', $args);
                if (count($args) < 3) {
                    break;
                }

                /* 指定ID的情况 */
                if (is_numeric($args[0])) {
                    $typeId = intval($args[0], 10);
                    $subTypeList = DB::table('arctypes')->where('top_id', $typeId)->get();
                    // 有子分类
                    if (!empty($subTypeList)) {
                        $typeIdList = array();
                        $typeIdList[] = $typeId;
                        foreach ($subTypeList as $value) {
                            $typeIdList[] = $value->id;
                        }
                        $subTypeList = null;
                        $typeIds = implode(',', $typeIdList);
                        $query = DB::table('archives')->whereRaw("typeid IN ({$typeIds}) AND arcrank=0")->orderBy('id', 'DESC');
                    } 
                    // 没有子分类
                    else {
                        $query = DB::table('archives')->whereRaw("typeid={$typeId} and arcrank=0")->orderBy('id', 'DESC');
                    }
                    break;
                }

                /*多个连续ID的情况*/
                if (false !== strpos($args[0], '-')) {
                    $nums = explode('-',$args[0]);
                    $ids = [];
                    for($i = $nums[0]; $i< $nums[1]; $i++){
                        $ids[] = $i;
                    }
                    $typeIds = join($ids,',');
                    $query = DB::table('archives')->whereRaw("typeid IN ({$typeIds}) AND arcrank=0")->orderByRaw('RAND()')->orderBy('id', 'desc');
                    break;
                }

                /* 多个分类ID的情况 */
                if (false !== strpos($args[0], '|')) {  
                    $typeIds = strtr($args[0], array('|' => ','));
                    $query = DB::table('archives')->whereRaw("typeid IN ({$typeIds}) AND arcrank=0")->orderBy('id', 'desc');
                    break;
                }
                
                /* 获取指定的随机 */
                $custom = array(0 => '随机', 1 => '文章', 2 => '图片', 3 => '视频');
                $show = array_search($args[0], $custom);
                if (false !== $show) {
                    if (0 === $show) {
                        $query = DB::table('archives')->where('arcrank', 0)->orderByRaw('RAND()');
                    } else {
                        $query = DB::table('archives')->where([ ['show', '=', $show], ['arcrank', '=', 0] ])->orderByRaw('RAND()');
                    }
                    break;
                }
                
                /* 单个模糊查询 名称、别名 */
                $typeId = null;
                $typeAll = DB::table('arctypes')->where('channel', 1)->get(['id','top_id','type_name'])->toArray();
                foreach ($typeAll as $value) {
                    if ($args[0] == $value->type_name) {
                        $typeId = $value->id;
                        break;
                    }
                }
                if (null !== $typeId) {
                    $query = DB::table('archives')->where([ ['typeid', '=', $typeId], ['arcrank', '=', 0] ])->orderBy('id', 'desc');
                    break;
                }
            }
            while (false);
            
            if (!empty($query)) {
                $liebiao = $query->offset($args[1])->limit($args[2])->get()->toArray();
                $liebiao = serialize($liebiao);
            } else {
                $liebiao = serialize(null);
            }
            
            // 获取模版名称
            if (isset($args[3])) {
                $template = getView('helpers.liebiao.' . $args[3]);
                return "<?php \$liebiao = unserialize('{$liebiao}'); ?>"."<?php echo \$__env->make('{$template}', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
            } else {
                return "<?php ob_start(); \$liebiao = unserialize('{$liebiao}'); ?>";
            }
        });
        Blade::directive('endliebiao', function($args) {
            return '<?php $html = ob_get_contents(); $html = renderTpl($liebiao,$html,"liebiao"); ob_end_clean(); echo $html; ?>';
        });
        
        /**
         * 自定义的列表标签助手方法
         *
         * @param string $args[0] "专题名称"
         * @param int $args[1] 查询的偏移量
         * @param int $args[2] 查询的限制数
         * @param int $args[3] 模版名称
         */
        Blade::directive('zhuanti', function($args) {
            do {
                /* 处理接收的参数 */
                $args = explode(',', $args);
                if (count($args) !== 4) {
                    break;
                }

                /* 取得对应的频道ID */
                $channel = array_search('专题', config('nizhan.channels'));
                if (empty($channel)) {
                    break;
                }
                $channel = intval($channel);

                /* 从该频道所有的栏目分类中，查找指定分类名称对应的ID */
                $typeName = $args[0];
                $typeId = null;
                if ($typeName !== '所有') {
                    $typeList = DB::table('arctypes')->where('channel', $channel)->pluck('type_name', 'id')->toArray();
                    $typeId = empty($typeList) ? null : array_search($args[0], $typeList);
                }

                /* 按条件查询数据库表 */
                if (empty($typeId)) {
                    $query = DB::table('topics');
                } else {
                    $query = DB::table('topics')->where([ ['type_id', '=', $typeId] ]);
                }

                $zhuanti = $query->orderBy('id', 'desc')->offset($args[1])->limit($args[2])->get()->toArray();
                $zhuanti = serialize($zhuanti);
                
                // 获取模版名称
                $template = getView('helpers.zhuanti.zhuanti'. $args[3]);

                return "<?php \$typeName = '{$typeName}'; \$zhuanti = unserialize('{$zhuanti}'); ?>"."<?php echo \$__env->make('$template', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
            }
            while (false);
        });
        
        /**
         * 栏目的标签
         * 
         * @param string $args[0] 栏目  如：ID 或 名称
         * @param int $args[1] 查询的偏移量  0
         * @param int $args[2] 查询的限制数 7
         * @param int $args[3] 控制是否返回栏目信息，默认为0, 1则显示栏目信息
         * @param int $args[4] 默认为导航 3，只有当args[0]为名称时有需要
         * @example 在Blade模版中使用如下示例
         * <pre>
         *    @lanmu(首页,0,7)
         *    @lanmu(视频,0,7)
         *    @lanmu(图片,0,7)
         * </pre>
         */
        Blade::directive('lanmu', function($args) {
            do {
                /* 处理接收的参数 */
                $args = explode(',', $args);
                if (count($args) < 3) {
                    break;
                }
                
                /* 栏目的分类参数，默认为导航【3】 */
                if (isset($args[4])) {
                    $channel = $args[4];
                } else {
                    $channel = 3;
                }
                
                /* 情况1：通过栏目ID获取 */
                if (is_numeric($args[0])) {
                    $lanmuId = intval($args[0], 10);
                } 
                /* 情况2：通过栏目名称获取一级栏目ID */
                else {
                    $typeList = DB::table('arctypes')->where([ ['channel', '=', $channel], ['top_id', '=', 0] ])->pluck('type_name', 'id')->toArray();
                    $lanmuId = empty($typeList) ? null : array_search($args[0], $typeList);
                    if (empty($lanmuId) || !is_numeric($lanmuId)) {
                        break;
                    }
                }
                
                /* 返回栏目信息 */
                if (!empty($args[3])) {
                    $lanmuList = DB::table('arctypes')->where('id', $args[0])->get();
                } else {
                    /* 按条件查询数据库表 */
                    $lanmuList = DB::table('arctypes')->where('top_id', $lanmuId)
                                   ->offset($args[1])->limit($args[2])
                                   ->orderBy('order', 'ASC')->get();
                }
                $lanmuList = serialize($lanmuList);
                
                return "<?php ob_start(); \$lanmu = unserialize('{$lanmuList}'); ?>";
            }
            while (false);
        });
        Blade::directive('endlanmu', function($args) {
            return '<?php $html = ob_get_contents(); $html = renderTpl($lanmu,$html,"lanmu"); ob_end_clean(); echo $html; ?>';
        });
        
        /**
         * 推荐的标签
         * 
         * @param string $args[0] 推荐位 [a-z]
         * @param int $args[1] 查询的偏移量
         * @param int $args[2] 查询的限制数
         * @example 在Blade模版中使用如下示例
         * <pre>
         *    @tuijian(a,0,3,a)
         * </pre>
         */
        Blade::directive('tuijian', function($args) {
            do {
                /* 处理接收的参数 */
                $args = explode(',', $args);
                if (count($args) < 3) {
                    break;
                }
                
                /* 按条件查询数据库表 */
                $tuijian = DB::table('archives')->whereRaw('arcrank=0 AND FIND_IN_SET("' . $args[0] .'",flag)')
                                ->orderBy('id', 'desc')->offset($args[1])->limit($args[2])->get();
                $tuijian = serialize($tuijian);

                // 获取模版名称
                if (isset($args[3])) {
                    $tpl = config('nizhan.recoms')[ $args[0] ];
                    $template = getView('helpers.tuijian.'.$tpl);
                    return "<?php \$tuijian = unserialize('{$tuijian}'); ?>"."<?php echo \$__env->make('{$template}', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
                } else {
                    return "<?php ob_start(); \$tuijian = unserialize('{$tuijian}'); ?>";
                }
            }
            while (false);
        });
        Blade::directive('endtuijian', function($args) {
            return '<?php $html = ob_get_contents(); $html = renderTpl($tuijian,$html,"tuijian"); ob_end_clean(); echo $html; ?>';
        });

        /**
         * 文章的标签
         * 
         * @param string $args[0] 随机
         * @param int $args[1] 查询的偏移量
         * @param int $args[2] 查询的限制数
         * @example 在Blade模版中使用如下示例
         * <pre>
         *    @tag('随机',0,3)
         * </pre>
         */
        Blade::directive('tag', function($args) {
            do {
                /* 处理接收的参数 */
                $args = explode(',', $args);
                if (count($args) < 3) {
                    break;
                }
                
                /* 按条件查询数据库表 */
                if ($args[0] === '随机') {
                    $tag = DB::table('tags')->orderByRaw('RAND()')->offset($args[1])->limit($args[2])->get();
                } else {
                    $tag = DB::table('tags')->orderBy('id', 'DESC')->offset($args[1])->limit($args[2])->get();
                }
                $tag = serialize($tag);
                return "<?php ob_start(); \$tag = unserialize('{$tag}'); ?>";
            }
            while (false);
        });
        Blade::directive('endtag', function($args) {
            return '<?php $html = ob_get_contents(); $html = renderTpl($tag,$html,"tag"); ob_end_clean(); echo $html; ?>';
        });
        
        /**
         * 英雄的标签
         *
         * @param string $args[0] 法师 等职业 或 所有
         * @param int $args[1] 查询的偏移量
         * @param int $args[2] 查询的限制数
         * @param int $args[3] 显示的样式 [1,hot,new]
         * @example 在Blade模版中使用如下示例
         * <pre>
         *    @yingxiong(战士,0,5,1)
         *    @yingxiong(所有,0,5,hot)
         * </pre>
         */
        Blade::directive('yingxiong', function($args) {
            do {
                $args = explode(',', $args);

                if (count($args) !== 4) {
                    break;
                }

                $heros = json_decode(config('nizhan.herolist'));
                $type = config('nizhan.herotype');
                $hero_type = array_search($args[0], $type);

                $yingxiong = [];
                if($hero_type > 0){
                    foreach ($heros as $hero) {
                        if($hero->hero_type === $hero_type){
                            $yingxiong[] = $hero;
                        }
                    }
                }else{
                    $yingxiong = $heros;
                }

                if($args[3] == 'hot' || $args[3] == 'new'){
                    $yingxiong = $heros = json_decode(config('nizhan.hero'.$args[3]));
                    $typeName = $args[3];
                    $args[3] = 2;
                }else{
                    $typeName = '';
                }

                $yingxiong = count($yingxiong) > $args[2] ? array_slice($yingxiong,$args[1],$args[2]) : $yingxiong ;
                $yingxiong = serialize($yingxiong);

                // 获取模版名称
                $template = getView('helpers.yingxiong.yingxiong'. $args[3]);
                
                return "<?php \$typeName = '{$typeName}'; \$yingxiong = unserialize('{$yingxiong}'); ?>"."<?php echo \$__env->make('{$template}', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
            }
            while (false);
        });
        
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}