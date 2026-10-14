<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationInlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_page_shows_inline_verify_button(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verify email now')
            ->assertSee(route('verification.verify', [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ], false), false);
    }

    public function test_resend_still_provides_inline_link_when_mailer_is_log(): void
    {
        config(['mail.default' => 'log']);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('verification_url');
    }
}
