<?php

namespace Tests\Unit;

use App\Services\KairoEaService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class KairoEaServiceTest extends TestCase
{
    #[Test]
    public function it_normalizes_section_titles_with_or_without_accents(): void
    {
        $method = new ReflectionMethod(KairoEaService::class, 'parseSecciones');
        $text = <<<'TEXT'
## 1. ANALISIS DE CAUSALIDAD
Contenido 1
## ACCIONES INSEGURAS IDENTIFICADAS
Contenido 2
## FACTORES CONTRIBUTIVOS
Contenido 3
## FACTORES ORGANIZACIONALES Y CULTURALES
Contenido 4
## LECCIONES APRENDIDAS
Contenido 5
## PLAN DE ACCION
Contenido 6
## CONCLUSION
Contenido 7
TEXT;

        $result = $method->invoke(new KairoEaService(), $text);

        $this->assertSame(KairoEaService::SECTIONS, array_keys($result));
        $this->assertCount(7, array_filter(
            $result,
            fn (string $content): bool => str_starts_with($content, 'Contenido')
        ));
    }
}
