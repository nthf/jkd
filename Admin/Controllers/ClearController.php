<?php

namespace Jkd\Admin\Controllers;

use App\Http\Controllers\Controller;

/**
 * 清理缓存
 */
class ClearController extends Controller
{
    /**
     * 删除预编译缓存
     */
    public function compile()
    {
        delDirFiles(realpath(public_path() . '/../storage/framework/views'));
        
echo <<<HTML
        <html>
        <head>
        <script>
             alert("清除成功!");
             window.location.href="/admin/";
        </script>
        </head>
        <body>
        </body>
        </html>
HTML;
    }
    
}
