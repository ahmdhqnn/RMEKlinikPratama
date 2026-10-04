<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nip',
        'sip',
        'specialty',
        'avatar_url',
        'phone',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** @param UserRole|string|array<int, UserRole|string> ...$roles */
    public function hasRole(UserRole|string|array ...$roles): bool
    {
        $roles = collect($roles)->flatten()->map(static fn (UserRole|string $role): string => $role instanceof UserRole ? $role->value : $role);

        return $roles->containsStrict($this->role);
    }

    public function isDokter(): bool
    {
        return $this->role === 'dokter';
    }

    public function isPerawat(): bool
    {
        return $this->role === 'perawat';
    }

    public function isFarmasi(): bool
    {
        return $this->role === 'farmasi';
    }

    public function isPendaftaran(): bool
    {
        return $this->role === 'pendaftaran';
    }

    public function isKepalaKlinik(): bool
    {
        return $this->role === 'kepala_klinik';
    }

    public function queuesAsDoctor()
    {
        return $this->hasMany(Queue::class, 'dokter_id');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'doctor_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}
