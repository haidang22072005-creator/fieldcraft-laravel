<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivechatTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_messages_persist_with_sender_receiver_and_read_state(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $message = Message::create([
            'sender_id' => $customer->id,
            'receiver_id' => $admin->id,
            'content' => 'Xin chào FIELDCRAFT',
            'is_read' => false,
        ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender_id' => $customer->id,
            'receiver_id' => $admin->id,
            'is_read' => false,
        ]);
        $this->assertSame($customer->id, $message->fresh()->sender->id);
        $this->assertSame($admin->id, $message->fresh()->receiver->id);
    }

    public function test_customer_sends_to_a_database_selected_admin_and_admin_replies(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($customer)
            ->postJson(route('chat.messages.store'), ['content' => 'Tôi cần hỗ trợ', 'receiver_id' => $otherCustomer->id])
            ->assertCreated()
            ->assertJsonPath('data.0.content', 'Tôi cần hỗ trợ');

        $message = Message::query()->latest('id')->firstOrFail();
        $this->assertSame($customer->id, $message->sender_id);
        $this->assertSame($admin->id, $message->receiver_id);

        $this->actingAs($admin)
            ->postJson(route('admin.chat.customers.messages.store', $customer), ['content' => 'FIELDCRAFT đang xử lý'])
            ->assertCreated()
            ->assertJsonPath('data.1.content', 'FIELDCRAFT đang xử lý');

        $this->actingAs($customer)
            ->getJson(route('chat.messages.index'))
            ->assertOk()
            ->assertJsonPath('data.1.content', 'FIELDCRAFT đang xử lý');
    }

    public function test_admin_customer_list_has_preview_and_unread_count_without_exposing_other_users(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        Message::create(['sender_id' => $customer->id, 'receiver_id' => $admin->id, 'content' => 'Cần tư vấn size', 'is_read' => false]);

        $response = $this->actingAs($admin)->getJson(route('admin.chat.customers.index'))->assertOk();

        $response->assertJsonPath('data.0.id', $customer->id);
        $response->assertJsonPath('data.0.last_message_content', 'Cần tư vấn size');
        $response->assertJsonPath('data.0.unread_messages_count', 1);
        $response->assertJsonMissing(['id' => $otherCustomer->id]);
    }

    public function test_customer_history_is_bounded_to_newest_hundred_in_chronological_order_and_marks_reads(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $base = now()->startOfSecond();

        foreach (range(1, 105) as $number) {
            Message::create([
                'sender_id' => $admin->id,
                'receiver_id' => $customer->id,
                'content' => 'Message '.$number,
                'is_read' => false,
                'created_at' => $base->copy()->addSeconds($number),
                'updated_at' => $base->copy()->addSeconds($number),
            ]);
        }

        $messages = $this->actingAs($customer)
            ->getJson(route('chat.messages.index'))
            ->assertOk()
            ->json('data');

        $this->assertCount(100, $messages);
        $this->assertSame('Message 6', $messages[0]['content']);
        $this->assertSame('Message 105', $messages[99]['content']);
        $this->assertSame(range(6, 105), array_map(fn (array $message): int => (int) str_replace('Message ', '', $message['content']), $messages));
        $this->assertSame(0, Message::where('receiver_id', $customer->id)->where('is_read', false)->count());
    }

    public function test_admin_open_marks_customer_messages_read_and_customer_cannot_read_another_conversation(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        Message::create(['sender_id' => $customer->id, 'receiver_id' => $admin->id, 'content' => 'Customer message', 'is_read' => false]);
        Message::create(['sender_id' => $otherCustomer->id, 'receiver_id' => $admin->id, 'content' => 'Other customer secret', 'is_read' => false]);

        $this->actingAs($admin)->getJson(route('admin.chat.customers.messages', $customer))->assertOk();
        $this->assertSame(0, Message::where('sender_id', $customer->id)->where('is_read', false)->count());
        $this->assertSame(1, Message::where('sender_id', $otherCustomer->id)->where('is_read', false)->count());

        $this->actingAs($customer)->getJson(route('chat.messages.index'))->assertJsonMissing(['content' => 'Other customer secret']);
    }

    public function test_chat_authorization_validation_and_missing_admin_are_truthful(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($customer)->getJson(route('admin.chat.customers.index'))->assertForbidden();
        $this->actingAs($customer)->getJson(route('admin.chat.customers.messages', $otherCustomer))->assertForbidden();
        $this->actingAs($admin)->getJson(route('admin.chat.customers.messages', $admin))->assertNotFound();
        $this->actingAs($customer)->postJson(route('chat.messages.store'), ['content' => ''])->assertStatus(422);
        $this->actingAs($customer)->postJson(route('chat.messages.store'), ['content' => str_repeat('x', 5001)])->assertStatus(422);

        $admin->delete();
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $this->actingAs($customer)->postJson(route('chat.messages.store'), ['content' => 'Super admin hỗ trợ'])->assertCreated();
        $this->assertDatabaseHas('messages', ['content' => 'Super admin hỗ trợ', 'receiver_id' => $superAdmin->id]);
        $superAdmin->delete();
        $this->actingAs($customer)->postJson(route('chat.messages.store'), ['content' => 'Xin trợ giúp'])->assertStatus(503)->assertJsonPath('message', 'Hiện chưa có quản trị viên trực tuyến.');
    }

    public function test_customer_surfaces_include_safe_livechat_popup_contract(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        foreach ([route('store.home'), route('settings')] as $url) {
            $response = $this->actingAs($customer)->get($url)->assertOk();
            $response->assertSee('HỖ TRỢ TRỰC TUYẾN', false);
            $response->assertSee('FIELDCRAFT SUPPORT', false);
            $response->assertSee('fieldcraft-livechat-panel', false);
            $response->assertSee('textContent', false);
            $response->assertSee('createElement', false);
        }
    }

    public function test_admin_chat_page_is_role_protected_separate_and_safe(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($customer)->get(route('admin.chat'))->assertForbidden();

        $response = $this->actingAs($admin)->get(route('admin.chat'))->assertOk();
        $response->assertSee('CHAT NHANH', false);
        $response->assertSee('chat-customers-list', false);
        $response->assertSee('chat-messages-thread', false);
        $response->assertSee('textContent', false);
        $response->assertSee('createElement', false);
        $response->assertSee('@media', false);
        $response->assertDontSee('innerHTML', false);
    }

    public function test_livechat_content_is_plain_text_and_support_center_remains_separate(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $xss = '<img src=x onerror=alert(1)>';

        $this->actingAs($customer)
            ->postJson(route('chat.messages.store'), ['content' => $xss])
            ->assertCreated()
            ->assertJsonPath('data.0.content', $xss);

        $this->actingAs($customer)
            ->postJson(route('support.tickets.store'), ['subject' => 'Ticket remains separate', 'category' => 'other', 'message' => 'Support center message'])
            ->assertCreated();

        $this->assertDatabaseHas('messages', ['content' => $xss, 'sender_id' => $customer->id, 'receiver_id' => $admin->id]);
        $this->assertDatabaseHas('support_tickets', ['user_id' => $customer->id, 'subject' => 'Ticket remains separate']);
        $component = file_get_contents(resource_path('views/components/livechat.blade.php'));
        $this->assertStringContainsString('content.textContent = messageData.content ||', $component);
        $this->assertStringNotContainsString('innerHTML', $component);
    }
}
