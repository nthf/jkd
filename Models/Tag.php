<?php

namespace Jkd\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public function archive()
    {
        return $this->belongsToMany(Archive::class);
    }

}
