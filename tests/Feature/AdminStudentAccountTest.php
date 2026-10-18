<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStudentAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_student_password_to_lrn_and_first_name(): void
    {
        $admin = $this->createUser(1, 'admin@example.com', 'Admin');
        $student = $this->createUser(3, 'student@example.com', 'Mia', [
            'id_number' => '123456789012',
            'password' => 'old-password',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.reset-student-password', $student->id, false))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('123456789012mia', $student->fresh()->password));
    }

    public function test_admin_update_regenerates_student_username_from_lrn_and_first_name(): void
    {
        $admin = $this->createUser(1, 'admin@example.com', 'Admin');
        $student = $this->createUser(3, 'student@example.com', 'Mia', [
            'id_number' => '123456789012',
            'student_id' => 'STU-001',
            'grade_level' => '11',
            'strand' => 'STEM',
            'section' => 'Grade 11 A',
        ]);

        DB::table('academic_sections')->insert([
            'grade_level' => '11',
            'section_name' => 'Grade 11 A',
            'strand' => 'STEM',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createUser(3, 'duplicate@example.com', 'Other', [
            'id_number' => '123456789014',
            'student_id' => 'STU-002',
            'username' => '123456789013nina',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $student->id, false), [
                'role_id' => 3,
                'first_name' => 'Nina',
                'last_name' => 'Student',
                'middle_name' => '',
                'id_number' => '123456789013',
                'student_id' => 'STU-001',
                'username' => 'manually-entered',
                'gender' => 'Female',
                'grade_level' => '11',
                'strand' => 'STEM',
                'section' => 'Grade 11 A',
                'phone_number' => '09123456789',
                'parent_name' => 'Parent',
                'parent_relationship' => 'Mother',
                'parent_phone_number' => '09123456788',
                'reset_to_default_password' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'username' => '123456789013nina2',
            'first_name' => 'Nina',
            'id_number' => '123456789013',
        ]);
        $this->assertTrue(Hash::check('123456789013nina', $student->fresh()->password));
    }

    public function test_admin_student_management_form_uses_configured_placement_options(): void
    {
        $admin = $this->createUser(1, 'admin@example.com', 'Admin');
        DB::table('academic_sections')->insert([
            'grade_level' => '11',
            'section_name' => 'Grade 11 A',
            'strand' => 'STEM',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'student'], false))
            ->assertOk()
            ->assertSee('Reset Student Password')
            ->assertSee('getFilteredSections(createForm.grade_level, createForm.strand)', false)
            ->assertSee('Emergency Contact Phone Number');
    }

    private function createUser(int $roleId, string $email, string $firstName, array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'role_id' => $roleId,
            'first_name' => $firstName,
            'last_name' => 'Student',
            'email' => $email,
            'username' => $email,
            'id_number' => '999999999999',
            'student_id' => 'STU-DEFAULT',
            'gender' => 'Female',
            'password' => 'old-password',
        ], $attributes));
    }
}
