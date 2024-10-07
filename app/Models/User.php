<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use App\DTOs\ConsentGrant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'consent',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'consent',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
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

    /**
     * Get the workspaces for the user.
     */
    public function workspaces()
    {
        return $this->hasMany(Workspace::class);
    }

    protected function consent(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                $arr = json_decode($value, true);

                return array_map(function ($entry) {
                    return new ConsentGrant(
                        Carbon::parse($entry['timestamp']),
                        $entry['terms_version'],
                        $entry['privacy_version'],
                        $entry['consent_url'],
                        $entry['consent_language']
                    );
                }, $arr);
            },
            set: function (array $value) {
                return json_encode(array_map(function (ConsentGrant $grant) {
                    return [
                        'timestamp' => $grant->timestamp->toDateTimeString(),
                        'terms_version' => $grant->terms_version,
                        'privacy_version' => $grant->privacy_version,
                        'consent_url' => $grant->consent_url,
                        'consent_language' => $grant->consent_language,
                    ];
                }, $value));
            }
        );
    }
}
