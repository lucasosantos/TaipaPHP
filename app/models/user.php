<?php
namespace App\Models;

use App\Core\Model;

class User extends Model {
    protected string $table = "user";
    
    // Colunas que podem ser preenchidas em massa
    protected array $fillable = [
        'username',
        'email',
        'level',
        'password'
    ];
    
    // Colunas protegidas (não podem ser preenchidas em massa)
    protected array $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
}