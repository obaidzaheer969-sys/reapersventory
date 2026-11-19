<?php

namespace Tests\Feature\Earning;

use App\Enums\TaskType;
use App\Enums\WalletTransactionType;
use App\Filament\Pages\CampaignFeeSettings;
use App\Filament\Pages\SiteAdsSettings;
use App\Filament\Pages\WithdrawalSettings;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Earners\Pages\ListEarners;
use App\Filament\Resources\EarningTasks\Pages\CreateEarningTask;
use App\Filament\Resources\EarningTasks\Pages\ListEarningTasks;
use App\Models\EarningTask;
use App\Models\SystemSetting;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_only_the_earning_system(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Earners');
        $response->assertSee('Pending payouts');
        $response->assertSee('Campaigns');
        $response->assertSee('Deposits');
        $response->assertSee('Cashmaal settings');
        $response->assertSee('Site ads &amp; approval', false);
        $response->assertSee('Withdrawal settings');
        $response->assertSee('Campaign fee settings');
        $response->assertDontSee('Businesses');
        $response->assertDontSee('Subscriptions');
        $response->assertDontSee('Payment methods');
    }

    public function test_admin_login_shows_the_reapers_growth_brand_and_logo(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Reapers Growth')
            ->assertSee('reapersventory-logo.png');
    }

    public function test_admin_task_table_only_lists_system_tasks(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $systemTask = EarningTask::factory()->youtubeSubscribe()->create();
        $campaign = EarningTask::factory()->campaign(100)->create();

        Livewire::actingAs($admin)
            ->test(ListEarningTasks::class)
            ->assertCanSeeTableRecords([$systemTask])
            ->assertCanNotSeeTableRecords([$campaign]);
    }

    public function test_admin_can_create_a_task_with_a_custom_reward_amount(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test(CreateEarningTask::class)
            ->fillForm([
                'title' => 'High reward task',
                'task_type' => TaskType::YoutubeSubscribe->value,
                'target_url' => 'https://www.youtube.com/@earnpk',
                'reward' => 500,
                'duration_seconds' => 15,
                'sort_order' => 0,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = EarningTask::query()->system()->sole();

        $this->assertSame(500.0, (float) $task->reward);
    }

    public function test_admin_campaign_table_only_lists_user_campaigns(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $systemTask = EarningTask::factory()->youtubeSubscribe()->create();
        $campaign = EarningTask::factory()->campaign(100)->create();

        Livewire::actingAs($admin)
            ->test(ListCampaigns::class)
            ->assertCanSeeTableRecords([$campaign])
            ->assertCanNotSeeTableRecords([$systemTask]);
    }

    public function test_admin_can_enable_site_approval_and_ads(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/admin/site-ads-settings')->assertOk();

        Livewire::actingAs($admin)
            ->test(SiteAdsSettings::class)
            ->set('data.site_approved', true)
            ->set('data.site_verification_tag', '<meta name="google-site-verification" content="verify-123">')
            ->set('data.ads_enabled', true)
            ->set('data.ad_provider', 'adsense')
            ->set('data.ad_slot_top', '<div data-test-ad>Admin ad</div>')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue((bool) SystemSetting::getValue('site_approved', false));
        $this->assertTrue((bool) SystemSetting::getValue('ads_enabled', false));
        $this->assertSame('<meta name="google-site-verification" content="verify-123">', SystemSetting::getValue('site_verification_tag'));
        $this->assertSame('<div data-test-ad>Admin ad</div>', SystemSetting::getValue('ad_slot_top'));
    }

    public function test_admin_can_hide_and_show_user_withdrawals(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/admin/withdrawal-settings')->assertOk();

        Livewire::actingAs($admin)
            ->test(WithdrawalSettings::class)
            ->set('data.withdrawals_enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse((bool) SystemSetting::getValue('withdrawals_enabled', true));

        Livewire::actingAs($admin)
            ->test(WithdrawalSettings::class)
            ->set('data.withdrawals_enabled', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue((bool) SystemSetting::getValue('withdrawals_enabled', true));
    }

    public function test_admin_can_hide_and_show_the_campaign_admin_fee(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/admin/campaign-fee-settings')->assertOk();

        Livewire::actingAs($admin)
            ->test(CampaignFeeSettings::class)
            ->set('data.campaign_admin_fee_visible', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse((bool) SystemSetting::getValue('campaign_admin_fee_visible', true));

        Livewire::actingAs($admin)
            ->test(CampaignFeeSettings::class)
            ->set('data.campaign_admin_fee_visible', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue((bool) SystemSetting::getValue('campaign_admin_fee_visible', true));
    }

    public function test_approval_badge_and_ads_are_shown_to_users_when_enabled(): void
    {
        $user = User::factory()->earner()->create();
        SystemSetting::setValue('site_approved', true);
        SystemSetting::setValue('site_verification_tag', '<meta name="google-site-verification" content="verify-123">');
        SystemSetting::setValue('ads_enabled', true);
        SystemSetting::setValue('ad_slot_top', '<div data-test-ad>Admin ad</div>');
        SystemSetting::setValue('ad_slot_footer', '<div data-test-footer-ad>Footer ad</div>');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Approved')
            ->assertSee('google-site-verification', false)
            ->assertSee('data-test-ad', false)
            ->assertSee('Admin ad', false)
            ->assertSee('data-test-footer-ad', false)
            ->assertSee('Footer ad', false);

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('data-test-ad', false)
            ->assertSee('Admin ad', false);
    }

    public function test_ads_do_not_run_without_a_site_verification_tag(): void
    {
        $user = User::factory()->earner()->create();
        SystemSetting::setValue('site_approved', true);
        SystemSetting::setValue('site_verification_tag', '');
        SystemSetting::setValue('ads_enabled', true);
        SystemSetting::setValue('ad_slot_top', '<div data-test-ad>Admin ad</div>');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Approved')
            ->assertDontSee('data-test-ad', false)
            ->assertDontSee('Admin ad', false);
    }

    public function test_approval_badge_and_ads_are_hidden_when_disabled(): void
    {
        $user = User::factory()->earner()->create();
        SystemSetting::setValue('site_approved', false);
        SystemSetting::setValue('site_verification_tag', '<meta name="google-site-verification" content="verify-123">');
        SystemSetting::setValue('ads_enabled', false);
        SystemSetting::setValue('ad_slot_top', '<div data-test-ad>Admin ad</div>');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Approved')
            ->assertDontSee('google-site-verification', false)
            ->assertDontSee('data-test-ad', false)
            ->assertDontSee('Admin ad', false);
    }

    public function test_duplicate_ad_scripts_are_rendered_only_once(): void
    {
        $user = User::factory()->earner()->create();
        $autoCode = '<script src="https://bauval.org/14/aaaaaaaaaaaaaaaa"></script>';
        $topCode = '<script src="https://bauval.org/21/bbbbbbbbbbbbbbbb"></script><div id="container-bbbbbbbbbbbbbbbb"></div>';

        SystemSetting::setValue('site_approved', true);
        SystemSetting::setValue('site_verification_tag', '<meta name="google-site-verification" content="verify-123">');
        SystemSetting::setValue('ads_enabled', true);
        SystemSetting::setValue('ad_auto_code', $autoCode);
        SystemSetting::setValue('ad_slot_top', $topCode);
        SystemSetting::setValue('ad_slot_content', $autoCode);
        SystemSetting::setValue('ad_slot_bottom', $topCode);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '/14/aaaaaaaaaaaaaaaa'));
        $this->assertSame(1, substr_count($html, '/21/bbbbbbbbbbbbbbbb'));
        $this->assertSame(1, substr_count($html, 'data-ad-slot="top"'));
        $this->assertSame(0, substr_count($html, 'data-ad-slot="content"'));
        $this->assertSame(0, substr_count($html, 'data-ad-slot="bottom"'));
    }

    public function test_onclick_ad_gate_loads_before_the_head_ad_script(): void
    {
        $user = User::factory()->earner()->create();
        $autoCode = '<script src="https://bauval.org/14/aaaaaaaaaaaaaaaa"></script>';

        SystemSetting::setValue('site_approved', true);
        SystemSetting::setValue('site_verification_tag', '<meta name="google-site-verification" content="verify-123">');
        SystemSetting::setValue('ads_enabled', true);
        SystemSetting::setValue('ad_auto_code', $autoCode);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('data-earn-ad-click-gate', $html);
        $this->assertStringContainsString('earn-to-pkr-ad-clicked-links', $html);
        $this->assertLessThan(
            strpos($html, '/14/aaaaaaaaaaaaaaaa'),
            strpos($html, 'data-earn-ad-click-gate'),
        );
    }

    public function test_onclick_ad_gate_is_hidden_when_ads_are_disabled(): void
    {
        $user = User::factory()->earner()->create();

        SystemSetting::setValue('site_approved', true);
        SystemSetting::setValue('site_verification_tag', '<meta name="google-site-verification" content="verify-123">');
        SystemSetting::setValue('ads_enabled', false);
        SystemSetting::setValue('ad_auto_code', '<script src="https://bauval.org/14/aaaaaaaaaaaaaaaa"></script>');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-earn-ad-click-gate', false);
    }

    public function test_onclick_ad_gate_is_hidden_without_a_head_ad_script(): void
    {
        $user = User::factory()->earner()->create();

        SystemSetting::setValue('site_approved', true);
        SystemSetting::setValue('site_verification_tag', '<meta name="google-site-verification" content="verify-123">');
        SystemSetting::setValue('ads_enabled', true);
        SystemSetting::setValue('ad_auto_code', '');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-earn-ad-click-gate', false);
    }

    public function test_guest_login_page_shows_ads_when_enabled(): void
    {
        SystemSetting::setValue('site_approved', true);
        SystemSetting::setValue('site_verification_tag', '<meta name="google-site-verification" content="verify-123">');
        SystemSetting::setValue('ads_enabled', true);
        SystemSetting::setValue('ad_slot_top', '<div data-test-ad>Admin ad</div>');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('google-site-verification', false)
            ->assertSee('data-test-ad', false)
            ->assertSee('Admin ad', false);
    }

    public function test_admin_can_add_balance_to_a_users_wallet(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $earner = User::factory()->earner()->create(['wallet_balance' => 0]);

        $this->actingAs($admin)->get('/admin/earners')->assertOk();

        Livewire::actingAs($admin)
            ->test(ListEarners::class)
            ->callAction(
                TestAction::make('addWallet')->table($earner),
                data: ['amount' => 500, 'description' => 'Manual top-up'],
            )
            ->assertHasNoActionErrors();

        $this->assertSame(500.0, (float) $earner->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $earner->id,
            'type' => WalletTransactionType::AdminCredit->value,
            'amount' => 500,
            'description' => 'Manual top-up',
        ]);
    }

    public function test_admin_can_delete_an_earner(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $earner = User::factory()->earner()->create();

        $this->actingAs($admin)->get('/admin/earners')->assertOk();

        Livewire::actingAs($admin)
            ->test(ListEarners::class)
            ->callAction(TestAction::make('delete')->table($earner))
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('users', ['id' => $earner->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->assertFalse($admin->can('delete', $admin));
    }
}
