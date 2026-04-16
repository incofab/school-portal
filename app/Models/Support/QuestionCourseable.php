<?php

namespace App\Models\Support;

use App\Models\CourseSession;
use App\Models\EventCourseable;
use App\Models\Instruction;
use App\Models\Passage;
use App\Models\Question;
use App\Models\TheoryQuestion;
use App\Support\MorphableHandler;
use Exception;
use Illuminate\Database\Eloquent\Model;

abstract class QuestionCourseable extends Model
{
    public function loadParent()
    {
        if ($this instanceof CourseSession) {
            return $this->load('course');
        } elseif ($this instanceof EventCourseable) {
            return $this->load('event');
        } else {
            throw new Exception('Invalid Question Courseable object');
        }
    }

    abstract public function getName();

    public function questions()
    {
        return $this->morphMany(Question::class, 'courseable');
    }

    public function theoryQuestions()
    {
        return $this->morphMany(TheoryQuestion::class, 'courseable');
    }

    public function instructions()
    {
        return $this->morphMany(Instruction::class, 'courseable');
    }

    public function passages()
    {
        return $this->morphMany(Passage::class, 'courseable');
    }

    public function getMorphedId()
    {
        return (new MorphableHandler)->getId($this);
    }
}
