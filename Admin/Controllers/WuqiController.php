<?php

namespace Jkd\Admin\Controllers;

use Jkd\Models\Wuqi;

use App\Http\Controllers\Controller;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;

class WuqiController extends Controller
{
    use ModelForm;

    /**
     * @return \Encore\Admin\Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('武器列表');
            $content->description('Arms List');

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

            $content->header('武器编辑');
            $content->description('Arms Edit');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * @return \Encore\Admin\Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('武器发布');
            $content->description('Arms Publish');

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
        return Admin::grid(Wuqi::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->column('name');
            $grid->filter(function ($filter) {
                $filter->like('name', '武器名称');
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {

        return Admin::form(Wuqi::class, function (Form $form){

            $form->display('id', 'ID');
            $form->text("name",'名称'); 
            $form->image("litpic",'缩略图'); 
            $form->textarea("intro",'武器简介')->rows(6)->rules('max:255');
            $form->textarea("ballistic","武器弹道")->rows(6);
            $form->editor('body', '武器内容')->attribute(['rows' => '20']);
        });
    }

}
