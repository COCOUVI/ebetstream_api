<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clan extends Model
{
    protected $fillable = ['name', 'description', 'owner_id'];
}
