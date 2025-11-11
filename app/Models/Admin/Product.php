<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected function casts(): array
    {
        return [
            'images' => 'array',
            'tags' => 'array',
            'scoresSection' => 'array',
            
            
        ];
    }

}
