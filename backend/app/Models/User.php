<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Logistics\StockMutation;
use App\Models\Logistics\StockOpname;
use App\Support\LegacyAccess;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'username', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
     * Get corresponding user ID in legacy SIMRS database (tbl_user).
     */
    public function getLegacyUserId(): ?int
    {
        if (empty($this->username)) {
            return null;
        }

        try {
            if (LegacyAccess::isAvailable('tbl_user')) {
                $id = DB::connection('legacy')
                    ->table('tbl_user')
                    ->where('username', $this->username)
                    ->where(function ($q) {
                        $q->whereNull('deleted_at')
                            ->orWhere('deleted_at', '0000-00-00 00:00:00')
                            ->orWhere('deleted_at', '0000-00-00');
                    })
                    ->value('id');

                return $id ? (int) $id : null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    public function createdMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'created_by');
    }

    public function validatedMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'validated_by');
    }

    public function createdStockOpnames(): HasMany
    {
        return $this->hasMany(StockOpname::class, 'created_by');
    }
}
