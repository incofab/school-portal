<?php

namespace App\Models;

use App\Enums\NoteStatusType;
use App\Rules\ValidateExistsRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NoteSubTopic extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'note_sub_topics';
    protected $guarded = [];

    protected $casts = [
        'note_topic_id' => 'integer',
        'status' => NoteStatusType::class,
    ];

    static function createRule()
    {
        return [
            'title' => ['required', 'string'],
            'content' => ['required', 'string']
        ];
    }

    public function noteTopic()
    {
        return $this->belongsTo(NoteTopic::class);
    }
}