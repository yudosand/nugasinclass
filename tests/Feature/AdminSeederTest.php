<?php
namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_requires_explicit_credentials_and_never_resets_existing_admin(): void
    {
        config(['initial_admin'=>['name'=>'Fixture Admin','phone'=>null,'email'=>null,'password'=>null]]);
        try {(new AdminSeeder)->run();$this->fail('Missing credentials accepted.');} catch (ValidationException $e) {$this->assertArrayHasKey('password',$e->errors());}
        $this->assertDatabaseCount('users',0);
        config(['initial_admin.phone'=>'081234560099','initial_admin.password'=>'FixtureOnlyPassword123!']);
        (new AdminSeeder)->run();$admin=User::first();
        $this->assertTrue(Hash::check('FixtureOnlyPassword123!',$admin->password));
        config(['initial_admin.password'=>'ChangedFixturePassword123!']);(new AdminSeeder)->run();
        $this->assertDatabaseCount('users',1);$this->assertSame($admin->password,$admin->fresh()->password);
    }

    public function test_seed_cannot_promote_an_existing_client_with_the_same_phone(): void
    {
        User::create(['name'=>'Client Fixture','nomor_hp'=>'081234560099','password'=>'FixtureOnlyPassword123!','role'=>'client','status'=>'approved']);
        config(['initial_admin'=>['name'=>'Admin Fixture','phone'=>'081234560099','email'=>null,'password'=>'FixtureOtherPassword123!']]);
        try {(new AdminSeeder)->run();$this->fail('Existing client promoted.');} catch (ValidationException $e) {$this->assertArrayHasKey('phone',$e->errors());}
        $this->assertDatabaseHas('users',['nomor_hp'=>'081234560099','role'=>'client']);
    }
}
