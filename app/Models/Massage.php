<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Massage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function getImageAttribute($image)
    {
        $image64 = base64_encode(Storage::get($image));
        return $image64;
    }
    protected $hidden = [
        'pivot'
    ];
    
    public function users(){
        return $this -> belongsToMany (User::class,"services"); 
    }
    // foreach ($massages as $massage) {
    //     $image64 = base64_encode(Storage::get($massage->image));
    //     if ($image64 != "" && Storage::exists($image64)) {

    //          $response['image'] = $image;
    //     }
    // }
}
