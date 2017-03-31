<?php

namespace Jkd\Models;

use Illuminate\Database\Eloquent\Model;

class Addonarticle extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['typeid','body'];
    //
    /**
     * 关联关系
     */
    public function archive()
    {
        return $this->belongsTo(Archive::class, 'aid');
    }
}
