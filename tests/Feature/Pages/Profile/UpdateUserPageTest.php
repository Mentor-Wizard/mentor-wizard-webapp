<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(RoleSeeder::class);
    $user = User::factory()->create();
    actingAs($user);
});

describe('Successful Scenarios', function (): void {
    it('renders the user page', function (): void {
        $this->get(route('profile.edit'))
            ->assertStatus(Response::HTTP_OK);
    });

    it('updates the username and email successfully', function ($filename): void {
        $user = User::factory()->create();

        Storage::fake('public');
        $file = UploadedFile::fake()->image($filename);

        $this->actingAs($user)->patch(route('user.update'), [
            'username'    => 'change_name',
            'email'       => 'change_email@email.com',
            'avatar'      => $file,
        ])
            ->assertStatus(Response::HTTP_FOUND)
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        expect($user->username)->toBe('change_name')
            ->and($user->email)->toBe('change_email@email.com');
        $this->assertDatabaseHas('media', [
            'file_name'    => $filename,
        ]);
    })->with(['avatar.png', 'avatar.jpeg', 'avatar.jpg', 'avatar.gif']);
});

describe('Unsuccessful Scenarios', function (): void {
    it('returns 404 for incorrect profile page address', function (): void {
        $this->get('/profil-ee')->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('does not allow username longer than the limit', function (): void {
        $user = User::factory()->create();

        $name = str_repeat('test', 300);
        $response = $this->patch(route('user.update'), [
            'username'  => $name,
            'email'     => 'change_email@email.com',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));
    });

    it('file extend is wrong', function (): void {
        $file = UploadedFile::fake()->create('avatar.doc', 100, 'application/msword');

        $response = $this
            ->patch(route('user.update'), [
                'username'    => 'change_name',
                'email'       => 'change_email@email.com',
                'avatar'      => $file,
            ]);

        $response->assertSessionHasErrors([
            'avatar' => 'The avatar field must be an image.',
        ]);
    });

    it('updates the username and email successfully', function (): void {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.docx')->size(2000);

        $response = $this->actingAs($user)->patch(route('user.update'), [
            'username'    => 'change_name',
            'email'       => 'change_email@email.com',
            'avatar'      => $file,
        ]);

        $response->assertSessionHasErrors([
            'avatar' => 'The avatar field must be a file of type: jpg, jpeg, png, gif.',
        ]);
    });

    it('file size is wrong', function (): void {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.png')->size(2000);

        $response = $this->actingAs($user)->patch(route('user.update'), [
            'username'    => 'change_name',
            'email'       => 'change_email@email.com',
            'avatar'      => $file,
        ]);

        $response->assertSessionHasErrors([
            'avatar' => 'The avatar field must not be greater than 1024 kilobytes.',
        ]);
    });

    it('does not allow name shorter than the limit', function (): void {
        $user = User::factory()->create();

        $response = $this->patch(route('user.update'), [
            'username' => 'A',
            'email'    => 'change_email@email.com',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));
    });

    it('does not allow empty name', function (): void {
        $user = User::factory()->create();

        $response = $this->patch(route('user.update'), [
            'username' => '',
            'email'    => 'change_email@email.com',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));
    });

    it('does not allow non-unique email', function (): void {
        $user = User::factory()->create();
        $secondUser = User::factory()->create();

        $response = $this->patch(route('user.update'), [
            'username'  => 'change_name',
            'email'     => $secondUser->email,
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNotEquals($secondUser->email, $user->email);
    });

    it('does not allow email longer than the limit', function (): void {
        $user = User::factory()->create();

        $email = str_repeat('test', 300).'@admin.com';
        $response = $this->patch(route('user.update'), [
            'username'   => 'change_name',
            'email'      => $email,
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNotEquals($email, $user->email);
    });

    it('does not allow empty email', function (): void {
        $user = User::factory()->create();

        $response = $this->patch(route('user.update'), [
            'username'   => 'change_name',
            'email'      => '',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNotEquals('', $user->email);
    });
});
