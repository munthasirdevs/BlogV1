<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CommentEdit
 *
 * Tracks edit history for comments.
 *
 * @property int $id
 * @property int $comment_id
 * @property int $user_id
 * @property string $old_content
 * @property string $new_content
 * @property string|null $edit_reason
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $created_at
 */
class CommentEdit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'comment_id',
        'user_id',
        'old_content',
        'new_content',
        'edit_reason',
        'ip_address',
    ];

    /**
     * Get the comment this edit belongs to.
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }

    /**
     * Get the user who made the edit.
     */
    public function editor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the time since edit was made.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get a summary of the edit.
     */
    public function getSummaryAttribute(): string
    {
        $oldLength = strlen($this->old_content);
        $newLength = strlen($this->new_content);
        $diff = $newLength - $oldLength;

        if ($diff > 0) {
            return "Added {$diff} characters";
        } elseif ($diff < 0) {
            return "Removed " . abs($diff) . " characters";
        }

        return 'Content modified';
    }
}
