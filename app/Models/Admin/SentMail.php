<?php

namespace App\Models\Admin;

use App\Concerns\Listable;
use App\Enums\FilterType;
use App\Listeners\Admin\RecordSentMail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One mail the application sent — written only by {@see RecordSentMail}, read
 * on Admin → Sent Mails. Not audited: it *is* a log.
 *
 * @property int $id
 * @property string|null $mailable
 * @property string $to_address
 * @property string|null $subject
 * @property string|null $html
 * @property int|null $user_id
 * @property string|null $mailer
 * @property Carbon $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['mailable', 'to_address', 'subject', 'html', 'user_id', 'mailer', 'sent_at'])]
#[Hidden(['html'])]
class SentMail extends Model
{
    use Listable;

    /**
     * @var array<literal-string, FilterType>
     */
    public const array FILTERABLE = [
        'to_address' => FilterType::Contains,
        'subject' => FilterType::Contains,
        'mailable' => FilterType::Equals,
    ];

    /**
     * @var list<string>
     */
    public const array SORTABLE = ['sent_at', 'to_address', 'subject'];

    public static function defaultSort(): string
    {
        return 'sent_at';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
