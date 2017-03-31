<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Jkd\Admin\Extensions;

use Encore\Admin\Tree;

/**
 * 扩展树形结构控件
 */
class Jkdtree extends Tree
{
    /**
     * @var \Closure
     */
    protected $queryCallback;
    
    /**
     * Build tree grid scripts.
     *
     * @return string
     */
    protected function script()
    {
        $token = csrf_token();

        $confirm = trans('admin::lang.delete_confirm');
        $saveSucceeded = trans('admin::lang.save_succeeded');
        $refreshSucceeded = trans('admin::lang.refresh_succeeded');
        $deleteSucceeded = trans('admin::lang.delete_succeeded');

        $nestableOptions = json_encode($this->nestableOptions);

        return <<<SCRIPT
        
        $('#{$this->elementId}').nestable($nestableOptions);

        $('#{$this->elementId} .tree_branch_delete').click(function() {
            var id = $(this).data('id');
            if(confirm("{$confirm}")) {
                $.post('/{$this->path}/' + id, {_method:'delete','_token':'{$token}'}, function(data){
                    $.pjax.reload('#pjax-container');
                    toastr.success('{$deleteSucceeded}');
                });
            }
        });

        $('.{$this->elementId}-save').click(function () {
            var serialize = $('#{$this->elementId}').nestable('serialize');

            $.post('/{$this->path}', {
                _token: '{$token}',
                _order: JSON.stringify(serialize)
            },
            function(data){
                $.pjax.reload('#pjax-container');
                toastr.success('{$saveSucceeded}');
            });
        });

        $('.{$this->elementId}-refresh').click(function () {
            $.pjax.reload('#pjax-container');
            toastr.success('{$refreshSucceeded}');
        });

        $('.{$this->elementId}-tree-tools').on('click', function(e){
            var target = $(e.target),
                action = target.data('action');
            if (action === 'expand') {
                $('.dd').nestable('expandAll');
            }
            if (action === 'collapse') {
                $('.dd').nestable('collapseAll');
            }
        });

SCRIPT;
    }
    
}
