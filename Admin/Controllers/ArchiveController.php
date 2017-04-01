<?php

namespace Jkd\Admin\Controllers;

use Jkd\Models\Archive;
use Jkd\Models\Arctype;
use Jkd\Models\Tag;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;

class ArchiveController extends Controller
{
    use ModelForm;

    /**
     * @return \Encore\Admin\Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('文章列表');
            $content->description('Archives List');

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

            $content->header('文章编辑');
            $content->description('Archives Edit');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * @return \Encore\Admin\Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('文章发布');
            $content->description('Archives Publish');

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
        return Admin::grid(Archive::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->column('title');
            
            $grid->column('flag', '推荐位')->sortable();

            $grid->arcrank('状态')->display(function ($arcrank) {
                if ($arcrank >= 0) {
                    return '正常';
                } elseif ($arcrank === -1) {
                    return '待审核';
                } else {
                    return '删除';
                }
            });
                        
            $grid->ismake('HTML')->display(function ($ismake) {
                return $ismake ? '已生成' : '未生成';
            });

            $grid->tags('标签')->display(function ($tags) {
                $tags = array_map(function ($tag) {
                    return "<span class='label label-success'>{$tag['tag_name']}</span>";
                }, $tags);
                return join('&nbsp;', $tags);
            });

            $grid->writer('发布人');

            $grid->updated_at();

            $grid->filter(function ($filter) {
                $filter->like('title', '标题');
                $filter->equal('typeid', '栏目ID');
                // 设置created_at字段的范围查询
                //$filter->between('created_at', '修改日期')->datetime();
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

        if(Request::has('type_id')){
            $type_id = Request::input('type_id');
        }else{
            $type_id = '';
        }

        return Admin::form(Archive::class, function (Form $form) use($type_id) {

//            $form->text('id', 'ID')->attribute(['id'=>'myarchive_id']);
            $form->display('id', 'ID');


            $states = [
                'on' => ['value' => 1, 'text' => '是'],
                'off' => ['value' => 0, 'text' => '否'],
            ];

            $tags = Tag::all()->pluck('tag_name', 'id');
            //$keywords = Keyword::all()->pluck('keyword','keyword');

            $form->text("title", '标题')->rules('required');
            $form->text("shorttitle", '简略标题');

            //这里只显示图文的模型与分类
//            $form->select('channel','模型')->attribute(['id'=>'mychannel'])->options(config('admin.channel'));
            //$form->display('channel_view','模型')->default('文章');
            $form->hidden('channel')->value(1);
            
            $form->radio('show', '归属')->options(config('admin.archiveshow'))->default(1);
            Arctype::setChannel(1);
            $form->select('typeid','分类')->options(Arctype::selectOptions())->default($type_id);

            $form->checkbox("flag",'推荐')->options(config('admin.flags'));
            $form->number("click",'点击数')->default(mt_rand(100,999))->attribute(['min' => 0, 'max'=> 1000000]);
            $form->text("writer",'作者')->default('NZ')/*->attribute(['readonly' => true])*/;
            $form->radio("arcrank",'状态')->options(config('admin.arcranks'));
            $form->switch("ismake",'是否生成')->help('审核状态下不会生成')->states($states);
            $form->image("litpic",'缩略图'); //->rules('required');
            $form->multipleSelect('tags','标签')->options($tags);
            $form->tags('keywords','关键字')->placeholder('输入关键字，多个之间用逗号"，"隔开');
            $form->textarea("description",'内容摘要')->rows(6)->rules('max:255')->help('255个字以内');
            $form->hidden("filename")->default('');
            $form->editor('detail.body', '内容详情')->attribute(['rows' => '20']);

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');

            $form->hidden('detail.source')->default('nizhan888.com');
            /* 选择分类时，改变对应隐藏区的值*/
            $form->hidden('detail.typeid')->default(0);
            $form->html('<script>$(".typeid").change(function(){
                        $("input[name=\"detail[typeid]\"").val(
                           $(this).find("option:selected").val()
                        );});
                        </script>');
            /*
            //图片直接用require判断 这里可以不用写了
            $form->saving(function (Form $form) {
                if ($form->_method === 'PUT') {
                    if ($form->litpic_action == 0) {
                        return;
                    }
                } else {
                    //对于新增的判断缩略图
                    if (is_uploaded_file($form->litpic) === true) {
                        return;
                    }
                }

                $error = new MessageBag([
                    'title' => '友情提醒',
                    'message' => '请先上传缩略图',
                ]);
                return back()->withInput()->with(compact('error'));
            });
            */
        });
    }



    /**
     * ajax得到文章对应的type_id
     */

    public function getArchiveTypeId()
    {

        $archive_id = Input::get('archive_id');
        $rest = Archive::find($archive_id);
        if($rest){
           $data =[
               'status'=>1,
               'msg'=>$rest->typeid,
           ];
        }else{
            $data = [
                'status'=>0,
                'msg'=>'文章typeid获取失败',
            ];
        }
        return $data;
    }

}
