<?php

namespace Jkd\Admin\Controllers;

use Jkd\Models\Topic;
use Jkd\Models\Arctype;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;

use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Request;

use App\Http\Controllers\Controller;

/**
 * 专题相关的控制器
 */
class TopicController extends Controller
{
    use ModelForm;

    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('专题列表');
            $content->description('Topic List');

            $content->body($this->grid());
        });
    }

    /**
     * @param $id
     * @return \Encore\Admin\Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('编辑专题');
            $content->description('Topic Edit');


            $content->body($this->form()->edit($id));
        });
    }

    /**
     * @return \Encore\Admin\Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('添加专题');
            $content->description('Topic Create');

            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Topic::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->column('name', '名称');
            $grid->column('type_id', '专题栏目')->display(function ($typeid) {
                static $typeList = array();
                if (array() === $typeList) {
                    $typeList = Arctype::where('channel', 2)->pluck('type_name', 'id')->toArray();
                }
                return isset($typeList[$typeid]) ? $typeList[$typeid] : '';
            });
            $grid->show('调用方式')->display(function ($show) {
                return config('admin.show')[$show];
            });

/*
            $grid->column('alias', '标签名称');
            $grid->column('img', '图片')->display(function ($img) {
                if (empty($img)) {
                    return '';
                }
                return '<img width="60" height="60" src="' . url(config('admin.upload.host') . $img) . '">';
            });
*/

            $grid->created_at();
            //$grid->updated_at();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {

        if(Request::has('type_id')){
            $type_id = Request::input('type_id');
        }else{
            $type_id = '';
        }

        return Admin::form(Topic::class, function (Form $form) use($type_id){
            Arctype::setChannel(2);

            $form->text('id', 'ID')->attribute(['id' => 'my_topic_id', 'disabled' => true]);

            $form->select2('type_id', '分类')->options(Arctype::selectOptionsForTopic())->attribute(['id' => 'type_id'])->default($type_id);

            $form->hidden('top_id')->attribute(['id' => 'top_id'])->default(0);

            $form->text('name', '名称')->rules('required');
            $form->text('alias', '标签名称');
            $form->text('link', '外部链接')->placeholder('输入链接（以“http://”开头）');
            $form->text('path', '栏目链接')->placeholder('输入栏目链接（以“/”开头）');
            $form->image('img', '图片');
            $form->radio("show", '调用方式')->options(config('admin.zhuantishow'))->default(0);
            $form->text('seo_title', 'SEO标题');
            $form->tags('seo_keywords', 'SEO关键词')->placeholder('  输入关键词，多个之间以逗号"，"隔开');
            $form->textarea('seo_description', 'SEO描述内容')->rows(3)->rules('max:255')->help('255个字以内');
            $form->html(getTopicJs());

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');

            $form->saving(function (Form $form) {
                if ($form->top_id == 0 || $form->type_id == 0) {
                    $error = new MessageBag([
                        'title' => '友情提醒',
                        'message' => '请先选择分类',
                    ]);
                    return back()->with(compact('error'));
                }

                $typeInfo = Arctype::find($form->type_id);
                if ($typeInfo && $typeInfo->top_id == 0) {
                    $error = new MessageBag([
                        'title' => '友情提醒',
                        'message' => '顶级分类请不要选择',
                    ]);
                    return back()->with(compact('error'));
                }
            });
        });
    }

}
