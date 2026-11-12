<?php

namespace Tests\Unit;

use App\Services\CodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_codes_start_at_one_on_an_empty_database(): void
    {
        $this->assertSame('A18-0001', CodeGenerator::car());
        $this->assertSame('JUAL-0001', CodeGenerator::sellRequest());
        $this->assertSame('TD-0001', CodeGenerator::testDrive());
        $this->assertSame('TRX-A18-1001', CodeGenerator::transaction());
    }

    public function test_codes_continue_after_the_seeded_data(): void
    {
        $this->seed();

        $this->assertSame('A18-0013', CodeGenerator::car());
        $this->assertSame('JUAL-0004', CodeGenerator::sellRequest());
        $this->assertSame('TD-0004', CodeGenerator::testDrive());
        $this->assertSame('TRX-A18-1006', CodeGenerator::transaction());
    }
}
