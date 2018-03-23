<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Telegram extends Model
{
    protected $table = 'telegram_command';

    protected $fillable = [
        'user_telegram','chat_id','last_command','added_find_keyword'
    ];
}
