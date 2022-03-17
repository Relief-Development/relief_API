<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    public function getImageAttribute($image)
    {
        $image64 = base64_encode(Storage::get($image));
        return $image64;
    }
}
