<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $table = "public.notification";
    protected $fillable = ['user_id', 'application_id', 'message', 'mode', 'status', 'created_at', 'updated_at'];
}
