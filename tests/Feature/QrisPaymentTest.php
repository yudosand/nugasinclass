<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Tests\TestCase;

class QrisPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $user = User::create(['name' => 'Pelanggan Uji', 'nomor_hp' => '081111111111', 'password' => 'test-password', 'role' => 'client', 'status' => 'pending_payment']);
        $package = Package::create(['name' => 'All Prodi Package', 'slug' => 'test-package', 'price' => 500000, 'description' => 'Tidak termasuk tugas praktikum']);
        return Order::create(['order_number' => 'ORD-TEST-QRIS', 'user_id' => $user->id, 'package_id' => $package->id, 'amount' => 500000, 'payment_status' => 'pending']);
    }

    private function preview(string $name, string $html): void
    {
        if ($directory = getenv('NUGASIN_PREVIEW_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0755, true);
            file_put_contents($directory.'/'.$name.'.html', $html);
        }
    }

    public function test_qris_is_shown_before_upload_without_whatsapp_confirmation(): void
    {
        $order = $this->order();
        $response = $this->actingAs($order->user)->get('/payment/'.$order->id);
        $response->assertOk()->assertSee('Pembayaran QRIS')->assertSee('qris-nugasin-class.png')
            ->assertSee('Unduh QRIS')->assertDontSee('Bank BCA')->assertDontSee('Bank Mandiri')
            ->assertDontSee('https://wa.me/6285861015161');
        $this->preview('payment-before', $response->getContent());
    }

    public function test_successful_upload_shows_exact_whatsapp_message_and_keeps_manual_approval(): void
    {
        Storage::fake('local');
        $order = $this->order();
        $this->actingAs($order->user)->post('/payment/'.$order->id.'/upload', [
            'payment_proof' => UploadedFile::fake()->image('bukti.png'),
        ])->assertRedirect('/payment/'.$order->id)->assertSessionHasNoErrors();
        $order->refresh();
        Storage::disk('local')->assertExists(substr($order->payment_proof, 8));
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('pending_approval', $order->user->status);
        $message = 'Halo Kak, saya sudah melakukan pembayaran ya. Mohon dibantu cek dan konfirmasi pembayarannya. Terima kasih 🙏';
        $response = $this->get('/payment/'.$order->id);
        $response->assertOk()->assertSee('https://wa.me/6285861015161?text='.rawurlencode($message), false)
            ->assertSee('Konfirmasi Pembayaran via WhatsApp');
        $this->preview('payment-after', $response->getContent());
    }

    public function test_pdf_proof_is_accepted(): void
    {
        Storage::fake('local');
        $order = $this->order();
        $pdf = UploadedFile::fake()->createWithContent('bukti.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $this->actingAs($order->user)->post('/payment/'.$order->id.'/upload', ['payment_proof' => $pdf])
            ->assertRedirect('/payment/'.$order->id)->assertSessionHasNoErrors();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->get('/payment/'.$order->id)->assertSee('Lihat Dokumen PDF')->assertSee('Konfirmasi Pembayaran via WhatsApp');
    }

    public function test_invalid_upload_does_not_unlock_confirmation(): void
    {
        Storage::fake('local');
        $order = $this->order();
        $this->actingAs($order->user)->from('/payment/'.$order->id)->post('/payment/'.$order->id.'/upload', [
            'payment_proof' => UploadedFile::fake()->createWithContent('bukti.txt', 'not a payment image'),
        ])->assertSessionHasErrors('payment_proof');
        $this->assertNull($order->fresh()->payment_proof);
        $this->get('/payment/'.$order->id)->assertDontSee('https://wa.me/6285861015161');
    }

    public function test_failed_storage_does_not_mark_order_paid(): void
    {
        $order = $this->order();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->actingAs($order->user)->post('/payment/'.$order->id.'/upload', [
            'payment_proof' => UploadedFile::fake()->image('bukti.png'),
        ])->assertSessionHasErrors('payment_proof');
        $this->assertNull($order->fresh()->payment_proof);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending_payment', $order->user->fresh()->status);
    }

    public function test_other_clients_cannot_view_or_upload_proof_for_this_order(): void
    {
        Storage::fake('local');
        $order = $this->order();
        $other = User::create(['name' => 'Other', 'nomor_hp' => '082222222222', 'password' => 'test-password']);
        $this->actingAs($other)->get('/payment/'.$order->id)->assertForbidden();
        $this->post('/payment/'.$order->id.'/upload', ['payment_proof' => UploadedFile::fake()->image('bukti.png')])->assertForbidden();
        $this->assertNull($order->fresh()->payment_proof);
    }
}
