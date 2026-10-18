<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $table = 'class_schedules';

    protected $guarded = [];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Renamed relationship to avoid collision with column names
    public function subjectRecord()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function academicSection()
    {
        return $this->belongsTo(AcademicSection::class, 'section_id');
    }

    public function getTimeSlotAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return '--:-- - --:--';
        }
        return Carbon::parse($this->start_time)->format('h:i A') . ' - ' . Carbon::parse($this->end_time)->format('h:i A');
    }
}