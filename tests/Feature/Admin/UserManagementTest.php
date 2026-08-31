<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression for FINAL_CODE_REVIEW.md H-3: "Users & Roles", explicitly
 * required in PROJECT_REQUIREMENTS.md §K, had no admin UI at all, and the
 * `role` column had zero behavioral effect — every active user, regardless
 * of role, could reach every admin controller. This suite proves the new
 * UserPolicy actually gates the module to super_admin, and that a super
 * admin cannot lock themselves out.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $contentEditor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->contentEditor = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);
    }

    public function test_guest_cannot_access_user_admin(): void
    {
        $this->get('/admin/users')->assertRedirect('/admin/login');
    }

    public function test_content_editor_cannot_access_user_management(): void
    {
        $this->actingAs($this->contentEditor)->get('/admin/users')->assertForbidden();
        $this->actingAs($this->contentEditor)->get('/admin/users/create')->assertForbidden();
    }

    public function test_super_admin_can_create_a_new_admin_user(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'name' => 'New Content Editor',
            'email' => 'editor@universalbrothers.test',
            'role' => 'content_editor',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'editor@universalbrothers.test', 'role' => 'content_editor', 'is_active' => true]);
    }

    public function test_super_admin_can_change_another_users_role_and_deactivate_them(): void
    {
        $response = $this->actingAs($this->superAdmin)->put("/admin/users/{$this->contentEditor->id}", [
            'name' => $this->contentEditor->name,
            'email' => $this->contentEditor->email,
            'role' => 'super_admin',
            'is_active' => '',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $this->contentEditor->id, 'role' => 'super_admin', 'is_active' => false]);
    }

    public function test_super_admin_cannot_demote_or_deactivate_their_own_account(): void
    {
        $response = $this->actingAs($this->superAdmin)->put("/admin/users/{$this->superAdmin->id}", [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'role' => 'content_editor',
            'is_active' => '',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'role' => 'super_admin', 'is_active' => true]);
    }

    public function test_super_admin_cannot_delete_their_own_account(): void
    {
        $response = $this->actingAs($this->superAdmin)->delete("/admin/users/{$this->superAdmin->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_super_admin_can_delete_another_admin_account(): void
    {
        $response = $this->actingAs($this->superAdmin)->delete("/admin/users/{$this->contentEditor->id}");

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $this->contentEditor->id]);
    }

    public function test_content_editor_cannot_delete_another_user(): void
    {
        $other = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);

        $response = $this->actingAs($this->contentEditor)->delete("/admin/users/{$other->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }
}
