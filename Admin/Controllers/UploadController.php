<?php

namespace Jkd\Admin\Controllers;

use Jkd\Http\Controllers\Controller;
use Storage;

/**
 * 上传
 */
class UploadController extends Controller
{
    /**
     * 上传图片
     *
     * @return Content
     */
    public function image()
    {
        do {
            if (empty($_FILES['image'])) {
                break;
            }
            
            if ($_FILES['image']['error'] > 0) {
                break;
            }
            
            $mines = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif');
            $mine = array_search($_FILES['image']['type'], $mines);
            if (!$mine) {
                break;
            }

            $tempFile = $_FILES['image']['tmp_name'];
            $contents = file_get_contents($tempFile);
            $fileName = md5($_FILES['image']['name']. uniqid()) . '.' . $mine;
            $ossPath = config('admin.upload.directory.image') . '/' ;

            $drive = Storage::drive('oss');

            if( !$drive->write($ossPath.$fileName, $contents) ){
                break;
            }

            echo config('admin.upload.host') . $ossPath . $fileName;
            die();
        }
        while (false);
        
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
    }
    
}

