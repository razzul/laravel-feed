<?php

namespace App;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table    = 'posts';
    public $timestamps  = true;
    protected $fillable = [
        'id',
        'user_id',
        "title",
        'content',
        "excerpt",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
