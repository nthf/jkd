<?php

namespace App\Admin\Controllers;

use Jkd\Models\Tag;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;

use Jkd\Http\Controllers\Controller;

/**
 * 标签管理
 */
class TagController extends Controller
{
    use ModelForm;

    /**
     * @return \Encore\Admin\Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('标签列表');
            $content->description('Tag List');

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

            $content->header('编辑标签');
            $content->description('Tag Edit');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * @return \Encore\Admin\Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('添加标签');
            $content->description('Create Tag');

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
        return Admin::grid(Tag::class, function (Grid $grid) {

            $grid->id('ID')->sortable();

            $grid->column('tag_name','标签名');
            $grid->column('path','标签链接');

            $grid->created_at();
            $grid->updated_at();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Tag::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->hidden('channel','模型')->default(1);

            $form->text('tag_name','标签名')->rules('required');
//            $form->text('alias','别名');
            $form->text('path','路径');
//            $form->text('link','标签链接');
//            $form->radio("show", '显示方式')->options(config('admin.show'));

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
