<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_reports_can_be_viewed_by_day_week_and_month(): void
    {
        [$teacher, $student, $sectionId] = $this->createTeacherAndSection();
        $this->recordAttendance($student, '2026-10-14', 'advisory', null, $sectionId, '08:05:00', '15:20:00');
        $this->recordAttendance($student, '2026-10-15', 'advisory', null, $sectionId, '08:10:00', '15:30:00');

        $this->actingAs($teacher)
            ->get(route('teacher.reports', [
                'report_by' => 'section',
                'section_id' => $sectionId,
                'period' => 'daily',
                'period_value' => '2026-10-14',
            ], false))
            ->assertOk()
            ->assertSee('8:05 AM')
            ->assertSee('3:20 PM')
            ->assertSee('Daily attendance')
            ->assertSee('Attendance Report');

        $this->actingAs($teacher)
            ->get(route('teacher.reports', [
                'report_by' => 'section',
                'section_id' => $sectionId,
                'period' => 'weekly',
                'period_value' => '2026-W42',
            ], false))
            ->assertOk()
            ->assertSee('Oct 12 – Oct 18, 2026')
            ->assertSee('P');

        $this->actingAs($teacher)
            ->get(route('teacher.reports', [
                'report_by' => 'section',
                'section_id' => $sectionId,
                'period' => 'monthly',
                'period_value' => '2026-10',
            ], false))
            ->assertOk()
            ->assertSee('October 2026')
            ->assertSee('P');
    }

    public function test_teacher_attendance_page_redirects_to_attendance_report(): void
    {
        [$teacher] = $this->createTeacherAndSection();

        $this->actingAs($teacher)
            ->get('/teacher/attendance')
            ->assertRedirect('/teacher/reports');
    }

    public function test_dashboard_distribution_groups_subjects_by_section_and_marks_advisory(): void
    {
        [$teacher, $femaleStudent, $sectionId] = $this->createTeacherAndSection();
        $femaleStudent->update(['gender' => '2']);

        $maleStudent = $this->createUser(3, 'distribution.male@example.com', 'Grade 11 A');
        $maleStudent->update(['gender' => '1']);
        DB::table('section_student')->insert([
            'student_id' => $maleStudent->id,
            'section_id' => $sectionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createSchedule($teacher, $sectionId, 'Algebra');
        $this->createSchedule($teacher, $sectionId, 'Geometry');

        $subjectSectionId = DB::table('academic_sections')->insertGetId([
            'grade_level' => '12',
            'section_name' => 'Grade 12 B',
            'advisor_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createSchedule($teacher, $subjectSectionId, 'Physics');
        $this->createSchedule($teacher, $subjectSectionId, 'Biology');

        $this->actingAs($teacher)
            ->getJson(route('teacher.dashboard', ['range' => 'daily', 'date' => now()->toDateString()], false))
            ->assertOk()
            ->assertJsonCount(2, 'subjectSummary')
            ->assertJsonPath('subjectSummary.0.label.0', 'Grade 11 A (Advisory)')
            ->assertJsonPath('subjectSummary.0.label.1', 'Algebra / Geometry')
            ->assertJsonPath('subjectSummary.0.subject_name', 'Algebra / Geometry')
            ->assertJsonPath('subjectSummary.0.students', 2)
            ->assertJsonPath('subjectSummary.0.female_count', 1)
            ->assertJsonPath('subjectSummary.0.male_count', 1)
            ->assertJsonPath('subjectSummary.1.label.0', 'Grade 12 B')
            ->assertJsonPath('subjectSummary.1.label.1', 'Physics / Biology')
            ->assertJsonPath('subjectSummary.1.subject_name', 'Physics / Biology');

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard', [], false))
            ->assertOk()
            ->assertSee('Student Distribution by Section')
            ->assertDontSee('Student Evaluation Progress');
    }

    public function test_teacher_absence_notifications_only_show_teacher_scoped_students(): void
    {
        Carbon::setTestNow('2026-10-16 12:00:00');

        try {
            [$teacher, , $sectionId] = $this->createTeacherAndSection();
            $this->createSchedule($teacher, $sectionId, 'Algebra');

            $this->actingAs($teacher)
                ->get(route('teacher.message', ['absence_date' => '2026-10-15'], false))
                ->assertOk()
                ->assertSee('Absence Notifications')
                ->assertSee('2026-10-15')
                ->assertSee('Student')
                ->assertSee('Send a custom message')
                ->assertDontSee('SMS Messages')
                ->assertDontSee('custom_number');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_advisory_teacher_can_update_student_username(): void
    {
        [$teacher, $student, $sectionId] = $this->createTeacherAndSection();
        $student->update(['username' => 'oldstudent']);

        $this->actingAs($teacher)
            ->put(route('teacher.students.update', $student->id, false), [
                'username' => 'newstudent',
                'first_name' => 'Updated',
                'middle_name' => '',
                'last_name' => $student->last_name,
                'id_number' => '123456789012',
                'phone_number' => '09123456789',
                'gender' => 'Female',
                'grade_level' => '11',
                'strand' => 'STEM',
                'section' => 'Grade 11 A',
                'parent_name' => 'Parent',
                'parent_relationship' => 'Mother',
                'parent_phone_number' => '09123456788',
            ])
            ->assertSessionHas('success', 'Student information updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'username' => '123456789012updated',
            'first_name' => 'Updated',
            'id_number' => '123456789012',
        ]);
    }

    public function test_advisory_teacher_can_add_student_with_username(): void
    {
        [$teacher, , $sectionId] = $this->createTeacherAndSection();

        $this->actingAs($teacher)
            ->post(route('teacher.students.store', [], false), [
                'username' => 'newstudent',
                'first_name' => 'New',
                'middle_name' => '',
                'last_name' => 'Student',
                'id_number' => 'NEW-STUDENT-01',
                'gender' => 'Female',
                'grade_level' => '11',
                'strand' => 'STEM',
                'section' => 'Grade 11 A',
                'phone_number' => '09123456789',
                'parent_name' => 'Parent',
                'parent_relationship' => 'Mother',
                'parent_phone_number' => '09123456788',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id_number' => 'NEW-STUDENT-01',
            'username' => 'newstudent01new',
            'role_id' => 3,
        ]);
        $this->assertDatabaseHas('section_student', [
            'section_id' => $sectionId,
            'student_id' => DB::table('users')->where('username', 'newstudent01new')->value('id'),
        ]);
    }

    public function test_teacher_student_forms_render_configured_academic_options(): void
    {
        [$teacher] = $this->createTeacherAndSection();

        $this->actingAs($teacher)
            ->get(route('teacher.students', [], false))
            ->assertOk()
            ->assertSee('getTeacherSections(newStudent.grade_level, newStudent.strand)', false)
            ->assertSee('getTeacherSections(form.grade_level, form.strand)', false)
            ->assertSee('name="username"', false)
            ->assertSee('Emergency Contact Phone');
    }

    public function test_subject_attendance_is_scoped_to_teacher_assigned_schedule(): void
    {
        [$teacher, $student, $sectionId] = $this->createTeacherAndSection();
        $scheduleId = $this->createSchedule($teacher, $sectionId, 'Algebra');
        $this->recordAttendance($student, '2026-10-14', 'subject', $scheduleId, null, '09:00:00', '10:00:00');

        $this->actingAs($teacher)
            ->get(route('teacher.reports', [
                'report_by' => 'subject',
                'schedule_id' => $scheduleId,
                'period' => 'daily',
                'period_value' => '2026-10-14',
            ], false))
            ->assertOk()
            ->assertSee('Algebra')
            ->assertSee('9:00 AM')
            ->assertSee('10:00 AM');

        $otherTeacher = $this->createUser(2, 'other.teacher@example.com');
        $otherScheduleId = $this->createSchedule($otherTeacher, $sectionId, 'Geometry');
        $this->actingAs($teacher)
            ->get(route('teacher.reports', ['report_by' => 'subject', 'schedule_id' => $otherScheduleId], false))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('teacher.attendance.export', [
                'report_by' => 'subject',
                'schedule_id' => $scheduleId,
                'period' => 'daily',
                'period_value' => '2026-10-14',
            ], false))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8');
    }

    private function createTeacherAndSection(): array
    {
        $teacher = $this->createUser(2, 'attendance.teacher@example.com');
        $student = $this->createUser(3, 'attendance.student@example.com', 'Grade 11 A');
        $sectionId = DB::table('academic_sections')->insertGetId([
            'grade_level' => '11',
            'section_name' => 'Grade 11 A',
            'advisor_id' => $teacher->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('section_student')->insert([
            'student_id' => $student->id,
            'section_id' => $sectionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$teacher, $student, $sectionId];
    }

    private function createUser(int $roleId, string $email, ?string $section = null): User
    {
        return User::query()->create([
            'id_number' => strtoupper(substr(md5($email), 0, 12)),
            'role_id' => $roleId,
            'first_name' => 'Test',
            'last_name' => $roleId === 3 ? 'Student' : 'Teacher',
            'email' => $email,
            'password' => bcrypt('password'),
            'section' => $section,
            'is_active' => true,
        ]);
    }

    private function createSchedule(User $teacher, int $sectionId, string $subjectName): int
    {
        return DB::table('class_schedules')->insertGetId([
            'section_id' => $sectionId,
            'teacher_id' => $teacher->id,
            'subject_name' => $subjectName,
            'section' => 'Grade 11 A',
            'day' => 'Wednesday',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function recordAttendance(
        User $student,
        string $date,
        string $type,
        ?int $scheduleId,
        ?int $sectionId,
        string $timeIn,
        string $timeOut,
    ): void {
        DB::table('attendance_logs')->insert([
            'user_id' => $student->id,
            'attendance_date' => $date,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'status' => 'ON-TIME',
            'class_schedule_id' => $scheduleId,
            'attendance_type' => $type,
            'advisory_section_id' => $sectionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
