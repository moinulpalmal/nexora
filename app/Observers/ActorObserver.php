<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Stamps who created and who last changed a record.
 *
 * Registered with `#[ObservedBy(ActorObserver::class)]` on every model carrying
 * `inserted_by` and `last_updated_by`; `grep -rl 'ObservedBy(ActorObserver'
 * app/Models` lists them. A model opts in by carrying the two columns and the
 * attribute, and there is nothing else to write.
 *
 * Neither column is mass-assignable and neither is ever set by hand: this class
 * is the only writer, so every write path is stamped identically. One shared
 * class rather than one per model precisely because "identically" stops being
 * true the moment a second copy is edited on its own.
 *
 * Writes with no authenticated actor (seeders, migrations, queued jobs) leave
 * the columns null, which is why both foreign keys are nullable.
 */
class ActorObserver
{
    /**
     * Handle the "creating" event for any stamped model.
     */
    public function creating(Model $model): void
    {
        $model->setAttribute('inserted_by', $model->getAttribute('inserted_by') ?? Auth::id());
    }

    /**
     * Handle the "updating" event for any stamped model.
     *
     * A console write keeps whatever `last_updated_by` already says, rather than
     * blanking a person's stamp with "nobody".
     */
    public function updating(Model $model): void
    {
        if (Auth::hasUser()) {
            $model->setAttribute('last_updated_by', Auth::id());
        }
    }
}
