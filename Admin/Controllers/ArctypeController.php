<?php

namespace Jkd\Admin\Controllers;

use Jkd\Models\Arctype;

use Encore\Admin\Form;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Tree;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\MessageBag;

use App\Http\Controllers\Controller;

/**
 * 栏目管理
 */
class ArctypeController extends Controller
{
    use ModelForm;

    /**
     * @return \Encore\Admin\Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {
            $content->header('栏目列表');
            $content->description('Column List');

            $content->row(function (Row $row) {
//                $row->column(6, $this->treeView()->render());
                $row->column(5, function (Column $column) {

                    $tab = new Tab();
                    foreach (config('admin.channel') as $key => $channel) {
//                        dd($this->treeView($key)->);
                        if ($key > 0) {
                            $tab->add($channel, $this->treeView($key)->render());
                        }
                    }
                    $column->append($tab);
                });


                $row->column(7, function (Column $column) {

                    $form = new \Encore\Admin\Widgets\Form();
                    $form->action(admin_url('arctypes'));
//
                    $form->text('type_name', '分类')->rules('required');

                    $form->select('channel', '模型')->attribute(['id' => 'mychannel'])->options(config('admin.channel'));
                    $form->select('top_id', '上级分类')->attribute(['id' => 'mytopid']);
                    $form->image('img', '图片');
                    $form->text('paths', '路径')->placeholder('输入（以“/”开头的路径）');
//                    $form->url('link', '链接')->placeholder('输入链接（以“http://”开头）');
//                    $form->tags('alias', '关键词')->placeholder('输入聚合关键词，以逗号“,”分隔开')->attibute(['id' => 'alias']);
//                    $form->radio("show", '显示方式')->options(config('admin.show'))->default(2);
                    $form->tags('alias', '聚合关键词')->placeholder('输入聚合关键词，多个以逗号“,”分隔开');
                    $form->text('seo_title', 'SEO标题');
                    $form->tags('seo_keywords', 'SEO关键词')->placeholder('  输入关键词，多个之间以逗号"，"隔开');
                    $form->textarea('seo_description', 'SEO摘要')->rows(5);
                    $form->hidden('order')->default(0);
                    $form->html(getArctypesJs());

                    $column->append((new Box(trans('admin::lang.new'), $form))->style('success'));
                });
            });
        });
    }


    /**
     * ajax得到分类结构化数据
     * @return array
     */
    public function getArctypesTree()
    {
        $msg = array();
        $inputArr = Input::all();

        //这里查询频道相关联的分类，添加
        $channelId = $inputArr['channel_id'];

        Arctype::setChannel($channelId);
        $restArr = Arctype::selectOptions();
        foreach ($restArr as $key => $rest) {
            if ($key > 0) {
                $arctypeObj = Arctype::find($key);
                $top_id = $arctypeObj->top_id;
                $msg[$key]['top_id'] = $top_id;
            } else {
                $msg[$key]['top_id'] = 0;
            }
            $msg[$key]['id'] = $key;
            $msg[$key]['typename'] = $rest;

        }
        $msg = array_merge($msg);

        if (empty($restArr) === false) {
            $data = [
                'status' => 1,
                'msg' => $msg,
            ];
        } else {
            $data = [
                'stauts' => 0,
                'msg' => '数据获取失败',
            ];
        }
        return $data;
    }

    /**
     * ajax得到分类对应的top_id
     */

    public function getArctypesTopId()
    {
//        dd(Input::all());
        $type_id = Input::all()['type_id'];
        $rest = Arctype::find($type_id);
        if ($rest) {
            $data = [
                'status' => 1,
                'msg' => $rest->top_id,
            ];
        } else {
            $data = [
                'status' => 0,
                'msg' => '分类topid获取失败',
            ];
        }
        return $data;
    }


    /**
     * 显示分类的树型结构
     */
    protected function treeView($channelId)
    {
//        global $cid;
//        $cid = $channelId;
        return Arctype::tree(function (Tree $tree) use ($channelId) {
            $tree->disableCreate();
            $tree->query(function ($model) use ($channelId) {
//                global $cid;
                return $model->where('channel', $channelId);
            });

            $tree->branch(function ($branch) {

                $a_href = '';

                if (empty($branch['paths']) === false) {
                    $a_href = env('APP_URL',  '') . $branch['paths'];

                } elseif (empty($branch['link'] === false)) {
                    $a_href = $branch['link'];
                }

                $payload = "&nbsp;{$branch['id']}--<strong>{$branch['type_name']}</strong>&nbsp;&nbsp;";

                if ($a_href !== null) {
                    $payload .= "<a href= '{$a_href}' class='dd-nodrag'>{$a_href}</a>";
                }
                $payload .= "<span class='pull-right dd-nodrag'><a data-channelid = '{$branch['channel']}' data-arctypeid='{$branch['id']}' href='javascript:void(0);' class='add-arc' style='margin: 0px 5px'><i class='fa fa-amazon'></i></a></span>";
                return $payload;
            });

        });
    }

    /**
     * @param $id
     * @return \Encore\Admin\Content
     */

    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('栏目修改');
            $content->description('Column Modify');

            $content->body($this->form()->edit($id));
        });
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        return Arctype::form(function (Form $form) {

            $form->text('id', 'ID')->attribute(['id' => 'myarctypeid', 'disabled' => 'disabled']);
//            $form->display('id', 'ID')->attribute(['id'=>'myarctypeid','disabled'=>'disabled']);

            $form->text('type_name', '分类')->rules('required');

            $form->select('channel', '模型')->rules('required')->attribute(['id' => 'mychannel'])->options(config('admin.channel'));
            $form->select('top_id', '上级分类')->attribute(['id' => 'mytopid']);

            $form->image('img', '图片');
            $form->text('paths', '路径')->placeholder('输入路径（以“/”开头）')->rules('required');
//            $form->text('link', '链接')->placeholder('输入链接（以“http://”开头）');
            $form->text('alias', '聚合关键词')->placeholder('输入聚合关键词，以逗号“,”分隔开');
            
//            $form->radio("show", '显示方式')->options(config('admin.show'));

            $form->text('seo_title', 'SEO标题');
            $form->tags('seo_keywords', 'SEO关键词')->placeholder('  输入关键词，多个之间以逗号"，"隔开');
            $form->textarea('seo_description', 'SEO摘要')->rows(5);
            $form->hidden('order')->default(0);
            $form->html(getArctypesJs());

            //提交之前查看，别名是否被使用过
            $form->saving(function (Form $form) {
                $request_all = Request::all();
                //得到模型id,//put->update
                if($form->_method === null && isset($request_all['_order']) === false) {
                    $channel_id = $form->channel;
                    if ($channel_id == 0) {
                        $error = new MessageBag([
                            'title' => '友情提醒',
                            'message' => '你必须选择一个模型',
                        ]);
                        return back()->with(compact('error'));
                    }
                    //得到分类名称
                    $type_name = $form->type_name;
                    $type_rest = Arctype::where('type_name', $type_name)->where('channel', $channel_id)->first();
                    if (isset($type_rest->id) === true) {
                        $error = new MessageBag([
                            'title' => '友情提醒',
                            'message' => '分类名称&nbsp;<strong style="color: #f1c40f;">' . $type_name . '</strong>&nbsp;已经使用过',
                        ]);
                        return back()->with(compact('error'));
                    }
                    
                    //检查路径是否存在，路径要唯一
                    $find = Arctype::where('paths', $form->paths)->first();
                    if (!empty($find)) {
                        $error = new MessageBag([
                            'title' => '友情提醒',
                            'message' => '路径&nbsp;<strong style="color: #f1c40f;">' . $form->paths . '</strong>&nbsp;已经用过',
                        ]);
                        return back()->with(compact('error'));
                    }
                }
            });
        });
    }


}
