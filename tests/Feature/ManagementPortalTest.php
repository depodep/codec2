<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManagementPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_login_redirects_to_its_own_portal_and_disabled_accounts_cannot_log_in(): void
    {
        $management = $this->createUser(4, 'management@example.com', [
            'username' => 'management@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('login.submit', [], false), [
            'username' => 'management@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('management.dashboard'));

        $this->post(route('logout', [], false));
        $management->update(['is_active' => false]);
        $this->post(route('login.submit', [], false), [
            'username' => 'management@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('username');
    }

    public function test_management_data_and_evaluation_routes_require_their_admin_granted_permissions(): void
    {
        $management = $this->createUser(4, 'management@example.com');

        $this->actingAs($management)
            ->get(route('management.dashboard', [], false))
            ->assertOk()
            ->assertSee('Dashboard and master-list access is not enabled')
            ->assertDontSee('Total Students');

        $this->actingAs($management)
            ->get(route('management.evaluations.index', [], false))
            ->assertForbidden();

        $management->update([
            'can_view_master_data' => true,
            'can_evaluate_faculty' => true,
        ]);

        $this->actingAs($management)
            ->get(route('management.dashboard', [], false))
            ->assertOk()
            ->assertSee('Today’s Attendance by Section')
            ->assertSee('School Master Lists');

        $this->actingAs($management)
            ->get(route('management.evaluations.index', [], false))
            ->assertOk()
            ->assertSee('Principal Faculty Evaluation');

        $this->actingAs($management)
            ->get(route('admin.dashboard', [], false))
            ->assertRedirect(route('management.dashboard'));
    }

    public function test_admin_can_toggle_management_login_and_assign_each_management_permission(): void
    {
        $admin = $this->createUser(1, 'admin@example.com');
        $management = $this->createUser(4, 'management@example.com', [
            'can_evaluate_faculty' => false,
            'can_view_master_data' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'management'], false))
            ->assertOk()
            ->assertSee('Evaluate faculty using the Principal Evaluation form')
            ->assertSee('View dashboard, attendance summaries, and master lists');

        $this->actingAs($admin)
            ->post(route('admin.users.toggle-status', $management->id, false))
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $management->id, 'is_active' => false]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $management->id, false), [
                'role_id' => 4,
                'first_name' => 'Management',
                'last_name' => 'User',
                'middle_name' => '',
                'email' => 'management@example.com',
                'student_id' => 'MNG-001',
                'id_number' => 'MNG-001',
                'gender' => 'Female',
                'phone_number' => '',
                'department' => 'Executive Management',
                'can_evaluate_faculty' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $management->id,
            'can_evaluate_faculty' => true,
            'can_view_master_data' => false,
            'is_principal' => true,
        ]);
    }

    public function test_management_can_submit_the_principal_evaluation_form_once_per_teacher_and_cycle(): void
    {
        $management = $this->createUser(4, 'management@example.com', ['can_evaluate_faculty' => true]);
        $teacher = $this->createUser(2, 'teacher@example.com', ['id_number' => 'TCH-001']);

        $periodId = DB::table('academic_periods')->insertGetId([
            'school_year' => '2026-2027',
            'semester' => '1st Semester',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cycleId = DB::table('evaluation_cycles')->insertGetId([
            'name' => 'Midyear Faculty Evaluation',
            'school_year' => '2026-2027',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $formId = DB::table('evaluation_forms')->insertGetId([
            'form_type' => 'principal',
            'title' => "Principal's Evaluation",
            'instructions' => 'Rate each item.',
            'version' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $questionId = DB::table('evaluation_questions')->insertGetId([
            'form_id' => $formId,
            'form_type' => 'principal',
            'category' => 'Instruction',
            'question' => 'Demonstrates mastery of the subject',
            'type' => 'likert',
            'is_required' => true,
            'order_num' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($management)
            ->get(route('management.evaluations.form', $teacher->id, false))
            ->assertOk()
            ->assertSee('Demonstrates mastery of the subject');

        $this->actingAs($management)
            ->post(route('management.evaluations.store', $teacher->id, false), [
                'answers' => [$questionId => 4],
                'comments' => 'Strong classroom practice.',
            ])
            ->assertRedirect(route('management.evaluations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('evaluation_submissions', [
            'evaluation_cycle_id' => $cycleId,
            'academic_period_id' => $periodId,
            'form_type' => 'principal',
            'student_id' => null,
            'evaluator_id' => $management->id,
            'teacher_id' => $teacher->id,
            'average_score' => 4,
        ]);

        $this->actingAs($management)
            ->get(route('management.evaluations.form', $teacher->id, false))
            ->assertRedirect(route('management.evaluations.index'))
            ->assertSessionHas('error');
    }

    public function test_management_dashboard_uses_database_counts_and_attendance_by_section(): void
    {
        $management = $this->createUser(4, 'management@example.com', ['can_view_master_data' => true]);
        $this->createUser(2, 'teacher@example.com');
        $firstStudent = $this->createUser(3, 'student1@example.com', [
            'id_number' => '123456789001',
            'section' => 'Sapphire',
            'grade_level' => '7',
        ]);
        $secondStudent = $this->createUser(3, 'student2@example.com', [
            'id_number' => '123456789002',
            'section' => 'Sapphire',
            'grade_level' => '7',
        ]);

        foreach ([[$firstStudent->id, 'ON-TIME'], [$secondStudent->id, 'LATE']] as [$studentId, $status]) {
            DB::table('attendance_logs')->insert([
                'user_id' => $studentId,
                'attendance_date' => today()->toDateString(),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($management)
            ->get(route('management.dashboard', [], false))
            ->assertOk()
            ->assertSee('Overall Attendance Rate')
            ->assertSee('100.0%')
            ->assertSee('Total Students')
            ->assertSee('2')
            ->assertSee('Total Teachers')
            ->assertSee('Sapphire')
            ->assertSee('Present')
            ->assertSee('Late');
    }

    private function createUser(int $roleId, string $email, array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'role_id' => $roleId,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'username' => $email,
            'id_number' => strtoupper('ID-'.str_replace(['@', '.'], '-', $email)),
            'student_id' => 'ID-DEFAULT',
            'gender' => 'Female',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ], $attributes));
    }
}
