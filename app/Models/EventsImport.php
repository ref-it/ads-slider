<?php

namespace App\Models;

use App\Enums\EventsImportSourceType;
use App\Traits\RealmTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventsImport extends Model
{
    use HasFactory, RealmTrait;

    protected $guarded = ['user_id', 'id'];

    /**
     * caldav_username/caldav_password must stay hidden: ImportEvents::import()
     * fills the new Event with $in->attributesToArray(), which would
     * otherwise leak the CalDAV credentials onto every imported event.
     */
    protected $hidden = ['realm_id', 'caldav_username', 'caldav_password'];

    protected $casts = [
        'disabled' => 'boolean',
        'not_closing' => 'boolean',
        'final_round_confirmed' => 'boolean',
        'is_karaoke' => 'boolean',
        'import_disabled' => 'boolean',
        'source_type' => EventsImportSourceType::class,
        'caldav_password' => 'encrypted',
    ];

    public function isCaldav(): bool
    {
        return $this->source_type === EventsImportSourceType::Caldav;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * get the events that were imported by the given import
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function disableAllCheckBoxes(): void
    {
        $this->attributes['not_closing'] = 0;
        $this->attributes['final_round_confirmed'] = 0;
        $this->attributes['is_karaoke'] = 0;
        $this->attributes['disabled'] = 0;
        $this->attributes['import_disabled'] = 0;
    }
}
