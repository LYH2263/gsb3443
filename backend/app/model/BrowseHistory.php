<?php

namespace app\model;

use think\Model;

class BrowseHistory extends Model
{
    protected $table = 'browse_history';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'id'       => 'integer',
        'user_id'  => 'integer',
        'album_id' => 'integer',
    ];

    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
