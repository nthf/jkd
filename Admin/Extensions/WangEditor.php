<?php

namespace Jkd\Admin\Extensions;

use Encore\Admin\Form\Field;

class WangEditor extends Field
{
    protected $view = 'admin::form.editor';

    protected static $css = [
        '/packages/admin/wangEditor/dist/css/wangEditor.min.css',
    ];

    protected static $js = [
        '/packages/admin/wangEditor/dist/js/wangEditor.js',
    ];

    public function render()
    {
        $token = csrf_token();

        $this->script = <<<EOT

var editor = new wangEditor('{$this->id}');
    editor.config.uploadImgFileName = 'image';
    editor.config.uploadImgUrl = '/admin/upload/image';
    editor.config.uploadParams = {
        _token: '$token'
    };
    editor.create();
EOT;
        return parent::render();

    }
}