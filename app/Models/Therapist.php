<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Therapist extends Model
{
    use HasFactory;

    public function users(){
        return $this->belongsToMany(User::class,'favorites');
    }

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
    ];
}
