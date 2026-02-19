<?php
namespace App\Models;

use App\Core\Model;

class Posts extends Model {
    protected string $table = "posts";

    protected array $views = [
        'public' => ['name', 'description'],
        'admin' => ['id', 'name', 'description', 'value', 'user_id']
        ];
    
    // Colunas que podem ser preenchidas em massa
    protected array $fillable = [
        'name',
        'description',
        'value',
        'user_id'
    ];
    
    // Colunas protegidas (não podem ser preenchidas em massa)
    protected array $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
}