<?php
/**
 * User Model
 * Version: 1.0
 * Created: 2026-01-13 07:30 GMT+11
 * 
 * Represents a system user with relationships to organisations and memberships
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'timezone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get organisations owned by this user
     */
    public function organisations()
    {
        return $this->hasMany(Organisation::class, 'owner_id');
    }

    /**
     * Get organisations this user is a member of
     */
    public function memberOrganisations()
    {
        return $this->belongsToMany(
            Organisation::class,
            'organisation_members',
            'user_id',
            'organisation_id'
        )->withTimestamps();
    }

    /**
     * Get all organisations (owned + member)
     */
    public function allOrganisations()
    {
        return $this->organisations()->union(
            $this->memberOrganisations()
        );
    }

    /**
     * Get predictions created by this user
     */
    public function predictions()
    {
        return $this->hasMany(CashFlowPrediction::class, 'created_by');
    }

    /**
     * Get imports created by this user
     */
    public function imports()
    {
        return $this->hasMany(ImportHistory::class, 'imported_by');
    }

    /**
     * Check if user owns an organisation
     */
    public function ownsOrganisation($organisationId)
    {
        return $this->organisations()->where('id', $organisationId)->exists();
    }

    /**
     * Check if user is member of an organisation
     */
    public function isMemberOfOrganisation($organisationId)
    {
        return $this->memberOrganisations()->where('organisation_id', $organisationId)->exists();
    }

    /**
     * Check if user has access to an organisation
     */
    public function hasAccessToOrganisation($organisationId)
    {
        return $this->ownsOrganisation($organisationId) || $this->isMemberOfOrganisation($organisationId);
    }

    /**
     * Get current timezone
     */
    public function getTimezoneAttribute()
    {
        return $this->attributes['timezone'] ?? 'UTC';
    }

    /**
     * Scope: Active users only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Search by name or email
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                     ->orWhere('email', 'like', "%{$term}%");
    }
}
