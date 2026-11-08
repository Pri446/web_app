<?php

namespace Tests\Unit;

use App\Http\Controllers\Security\PiiKeyBackupController;
use App\Models\PiiKeyBackupDownload;
use App\Models\User;
use App\Services\OwnerPiiAuditService;
use App\Services\PiiKeyBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PiiKeyBackupControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Controller behavior is tested without requiring the audit table.
        config(['pii.audit_enabled' => false]);
    }

    public function test_authorized_user_receives_a_no_store_key_download(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->request($user, [
            'current_password' => 'correct-password',
            'acknowledge_raw_key_risk' => '1',
        ]);
        $record = new PiiKeyBackupDownload();
        $record->setRawAttributes(['key_version' => 'v1']);

        $backups = Mockery::mock(PiiKeyBackupService::class);
        $backups->shouldReceive('canBackup')->once()->with($user)->andReturn(true);
        $backups->shouldReceive('rawBase64Contents')
            ->once()
            ->andReturn('YmFja3VwLWtleQ==');
        $backups->shouldReceive('recordDownload')
            ->once()
            ->with($user)
            ->andReturn($record);

        $response = $this->controller($backups)->download($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
        $this->assertStringContainsString(
            'pii-key-backup-v1-',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertSame(
            'application/octet-stream',
            $response->headers->get('Content-Type')
        );
    }

    public function test_wrong_password_never_reads_or_records_the_key(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->request($user, [
            'current_password' => 'wrong-password',
            'acknowledge_raw_key_risk' => '1',
        ]);

        $backups = Mockery::mock(PiiKeyBackupService::class);
        $backups->shouldReceive('canBackup')->once()->andReturn(true);
        $backups->shouldNotReceive('rawBase64Contents');
        $backups->shouldNotReceive('recordDownload');

        $response = $this->controller($backups)->download($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            'Password confirmation failed.',
            $response->getData(true)['message']
        );
    }

    public function test_unapproved_role_is_blocked_before_the_key_is_read(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->request($user, [
            'current_password' => 'correct-password',
            'acknowledge_raw_key_risk' => '1',
        ]);

        $backups = Mockery::mock(PiiKeyBackupService::class);
        $backups->shouldReceive('canBackup')->once()->andReturn(false);
        $backups->shouldNotReceive('rawBase64Contents');
        $backups->shouldNotReceive('recordDownload');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(
            'You are not authorized to back up the PII key.'
        );

        $this->controller($backups)->download($request);
    }

    public function test_risk_acknowledgment_is_required_before_key_read(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->request($user, [
            'current_password' => 'correct-password',
        ]);

        $backups = Mockery::mock(PiiKeyBackupService::class);
        $backups->shouldReceive('canBackup')->once()->andReturn(true);
        $backups->shouldNotReceive('rawBase64Contents');
        $backups->shouldNotReceive('recordDownload');

        $this->expectException(ValidationException::class);

        $this->controller($backups)->download($request);
    }

    private function controller(
        PiiKeyBackupService $backups
    ): PiiKeyBackupController {
        return new PiiKeyBackupController(
            $backups,
            app(OwnerPiiAuditService::class)
        );
    }

    private function request(User $user, array $data): Request
    {
        $request = Request::create('/security/pii-key-backup', 'POST', $data);
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session']->driver());
        $request->headers->set('Accept', 'application/json');

        return $request;
    }

    private function userWithPassword(string $password): User
    {
        $user = new User();
        $user->setRawAttributes([
            'id' => 101,
            'password' => Hash::make($password),
        ]);

        return $user;
    }
}
