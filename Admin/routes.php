<?php

use Illuminate\Routing\Router;

Route::group([
    'prefix'        => 'admin',
    'namespace'     => Admin::controllerNamespace(),
    'middleware'    => ['web', 'admin'],
    'domain' => 'admin.nizhan.dev'
], function (Router $router) {
    $router->get('/', 'HomeController@index');
    $router->resource('arctypes', ArctypeController::class);
    $router->resource('archives', ArchiveController::class);
    $router->resource('tags', TagController::class);
    $router->resource('topics', TopicController::class);
    $router->resource('wuqis', WuqiController::class);
    $router->post('/upload/image', 'UploadController@image');

    //获得栏目的结构化数据
    $router->get('getArctypesTree','ArctypeController@getArctypesTree');
    $router->get('getArctypesTopId','ArctypeController@getArctypesTopId');
    
    $router->get('getArchiveTypeId','ArchiveController@getArchiveTypeId');
    
    // 清理预编译缓存
    $router->get('/clear/compile', 'ClearController@compile');
});


