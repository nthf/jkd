<?php

namespace Jkd\Models;

use Illuminate\Database\Eloquent\Model;

class Addontmp extends Model
{
    //
    protected $fillable = ['aid','is_addon','g_source','created_at','updated_at'];


    //archive is model name
    protected $touches = ['archive'];

    public function archive()
    {
        return $this->belongsTo(Archive::class,'aid','id');
    }
}
