<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims(): array
    {
        return [];
    }
    public function petitions()
    {
        return $this->hasMany(Petition::class);
    }

    public function signedPetitions()
    {
        return $this->belongsToMany(Petition::class, 'petition_user')
            ->withTimestamps();
    }
    public function peticiones()
    {
        return $this->hasMany(Petition::class);
    }

    public function firmas()
    {
        return $this->belongsToMany(Petition::class, 'petition_user')
            ->withTimestamps();
    }

}
