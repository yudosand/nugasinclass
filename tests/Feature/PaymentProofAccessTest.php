<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofAccessTest extends TestCase
{
    use RefreshDatabase;
    private int $sequence = 0;

    private function user(string $role = 'client'): User
    {
        return User::create(['name' => 'Fixture '.++$this->sequence, 'nomor_hp' => '0819191910'.$this->sequence, 'password' => 'FixturePassword123!', 'role' => $role, 'status' => 'approved']);
    }

    private function order(): Order
    {
        $user = $this->user();
        $package = Package::create(['name' => 'Test', 'slug' => 'test-proof', 'price' => 100000]);
        return Order::create(['user_id' => $user->id, 'package_id' => $package->id, 'order_number' => 'TEST-PROOF', 'amount' => 100000, 'payment_status' => 'paid']);
    }

    private function png(): string
    {
        $file = UploadedFile::fake()->image('receipt.png');
        return file_get_contents($file->getPathname());
    }

    public function test_legacy_file_is_served_by_php_to_owner_and_admin_without_public_symlink(): void
    {
        Storage::fake('public');
        $order = $this->order();
        $bytes = $this->png();
        Storage::disk('public')->put('payment_proofs/legacy.png', $bytes);
        $order->update(['payment_proof' => 'payment_proofs/legacy.png']);
        $url = '/payment/'.$order->id.'/proof';
        $response = $this->actingAs($order->user)->get($url);
        $response->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $admin = $this->user('admin');
        $this->actingAs($admin)->withSession(['password_hash_web' => $admin->password])->get($url)->assertOk();
    }

    public function test_guest_and_other_client_cannot_read_the_proof(): void
    {
        $order = $this->order();
        $url = '/payment/'.$order->id.'/proof';
        $this->get($url)->assertRedirect('/login');
        $this->actingAs($this->user())->get($url)->assertForbidden();
    }

    public function test_new_upload_is_private_and_accessible_through_order_route(): void
    {
        Storage::fake('local'); Storage::fake('public');
        $order = $this->order();
        $this->actingAs($order->user)->post('/payment/'.$order->id.'/upload', ['payment_proof' => UploadedFile::fake()->image('new.png')])->assertRedirect('/payment/'.$order->id);
        $reference = $order->fresh()->payment_proof;
        $this->assertStringStartsWith('private/payment_proofs/', $reference);
        Storage::disk('local')->assertExists(substr($reference, 8));
        $this->assertEmpty(Storage::disk('public')->allFiles());
        $this->get('/payment/'.$order->id.'/proof')->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_missing_and_unsafe_paths_fail_without_disclosing_server_files(): void
    {
        Storage::fake('public'); Storage::fake('local');
        $order = $this->order();
        $this->actingAs($order->user);
        foreach (['payment_proofs/missing.jpg', 'payment_proofs/../../../.env', 'private/payment_proofs/../../.env', 'https://example.test/a.png', 'payment_proofs/a.php', null] as $reference) {
            $order->update(['payment_proof' => $reference]);
            $this->get('/payment/'.$order->id.'/proof')->assertNotFound()->assertSee('File bukti pembayaran belum ditemukan')->assertDontSee('APP_KEY=');
        }
    }

    public function test_html_disguised_as_image_is_rejected_and_pdf_is_downloaded(): void
    {
        Storage::fake('public');
        $order = $this->order();
        Storage::disk('public')->put('payment_proofs/invalid.jpg', '<html><script>alert(1)</script></html>');
        $order->update(['payment_proof' => 'payment_proofs/invalid.jpg']);
        $this->actingAs($order->user)->get('/payment/'.$order->id.'/proof')->assertStatus(415);
        Storage::disk('public')->put('payment_proofs/receipt.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $order->update(['payment_proof' => 'payment_proofs/receipt.pdf']);
        $response = $this->get('/payment/'.$order->id.'/proof')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
    }

    public function test_all_payment_links_use_authenticated_route_including_admin_views(): void
    {
        $order = $this->order();
        $order->update(['payment_proof' => 'payment_proofs/receipt.png']);
        $url = route('payment.proof', $order->id);
        $this->actingAs($order->user)->get('/payment/'.$order->id)->assertSee($url)->assertDontSee('/storage/payment_proofs/');
        $admin = $this->user('admin');
        $this->actingAs($admin)->withSession(['password_hash_web' => $admin->password]);
        foreach (['/admin/orders', '/admin/orders/'.$order->id, '/admin/clients/'.$order->user_id] as $page) {
            $this->get($page)->assertOk()->assertSee($url)->assertDontSee('/storage/payment_proofs/');
        }
    }

    public function test_deleting_client_removes_private_proof_and_preserves_other_files(): void
    {
        Storage::fake('local');
        $order = $this->order();
        $order->update(['payment_proof' => 'private/payment_proofs/receipt.png']);
        Storage::disk('local')->put('payment_proofs/receipt.png', $this->png());
        Storage::disk('local')->put('payment_proofs/other.png', $this->png());
        $client = $order->user;
        $this->actingAs($this->user('admin'))->delete('/admin/clients/'.$client->id, ['current_password' => 'FixturePassword123!', 'confirm_phone' => $client->nomor_hp])->assertRedirect('/admin/clients');
        Storage::disk('local')->assertMissing('payment_proofs/receipt.png');
        Storage::disk('local')->assertExists('payment_proofs/other.png');
    }
}
