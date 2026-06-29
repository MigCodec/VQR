<?php

namespace Tests\Unit;

use App\Models\VehicleDocument;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VehicleDocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-29 15:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_document_is_expiring_soon_when_thirty_calendar_days_or_less_remain(): void
    {
        $this->assertSame('valid', $this->documentExpiringIn(31)->computed_status);
        $this->assertSame('expiring_soon', $this->documentExpiringIn(30)->computed_status);
        $this->assertSame('expiring_soon', $this->documentExpiringIn(0)->computed_status);
        $this->assertSame('expired', $this->documentExpiringIn(-1)->computed_status);
    }

    private function documentExpiringIn(int $days): VehicleDocument
    {
        return new VehicleDocument([
            'status' => 'valid',
            'expires_at' => Carbon::today()->addDays($days)->toDateString(),
        ]);
    }
}
