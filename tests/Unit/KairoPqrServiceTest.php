<?php

namespace Tests\Unit;

use App\Services\KairoPqrService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class KairoPqrServiceTest extends TestCase
{
    #[Test]
    public function it_keeps_short_clinical_records_intact(): void
    {
        $method = new ReflectionMethod(KairoPqrService::class, 'prepararHistoria');
        $history = 'Registro clínico breve';

        $this->assertSame($history, $method->invoke(new KairoPqrService(), $history));
    }

    #[Test]
    public function it_compacts_large_clinical_records_preserving_beginning_and_end(): void
    {
        $method = new ReflectionMethod(KairoPqrService::class, 'prepararHistoria');
        $history = 'INICIO-'.str_repeat('a', 60000).'-FINAL';
        $result = $method->invoke(new KairoPqrService(), $history);

        $this->assertLessThan(45100, mb_strlen($result));
        $this->assertStringStartsWith('INICIO-', $result);
        $this->assertStringEndsWith('-FINAL', $result);
        $this->assertStringContainsString('REGISTROS INTERMEDIOS OMITIDOS', $result);
    }
}
