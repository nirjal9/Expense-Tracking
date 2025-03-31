<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name','budget','user_id'];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('budget_percentage');
    }
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


}
