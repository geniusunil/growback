<?php

namespace App\Models;
use App\Models\Activity;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function activities()
{
    return $this->hasMany(Activity::class);
}
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'is_deletion_scheduled',
        'deletion_scheduled_at',
        'deletion_due_at',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
      public static function appendFcmToken($user, $newToken)
    {
        if (!$user || empty($newToken)) return;
        try {
            $tokens = json_decode($user->fcm_token, true);
            if (!is_array($tokens)) {
                $tokens = array_filter(explode(',', (string)$user->fcm_token));
            }
            if (!in_array($newToken, $tokens)) {
                $tokens[] = $newToken;
            }
            $tokens = array_values(array_unique(array_filter($tokens)));
            $user->update(['fcm_token' => json_encode($tokens)]);
        } catch (\Exception $e) {}
    }
}
