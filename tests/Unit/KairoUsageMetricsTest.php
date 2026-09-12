<?php

namespace Tests\Unit;

use App\Services\KairoEaService;
use App\Services\KairoPqrService;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class KairoUsageMetricsTest extends TestCase
{
    public static function services(): array
    {
        return [[KairoPqrService::class], [KairoEaService::class]];
    }

    #[DataProvider('services')]
    public function test_it_aggregates_usage_from_every_openclaw_call(string $serviceClass): void
    {
        $first = Process::result(output: $this->responseJson(100, 20, 30, 5, 155));
        $second = Process::result(output: $this->responseJson(200, 40, 60, 10, 310));

        $method = new ReflectionMethod($serviceClass, 'agregarMetricas');
        $result = $method->invoke(new $serviceClass, [$first, $second]);

        $this->assertSame(300, $result['tokens_entrada']);
        $this->assertSame(60, $result['tokens_salida']);
        $this->assertSame(105, $result['tokens_cache']);
        $this->assertSame(465, $result['tokens_totales']);
        $this->assertSame('gpt-test', $result['modelo']);
    }

    private function responseJson(int $input, int $output, int $cacheRead, int $cacheWrite, int $total): string
    {
        return json_encode([
            'result' => [
                'payloads' => [['text' => 'resultado']],
                'meta' => ['agentMeta' => [
                    'model' => 'gpt-test',
                    'usage' => compact('input', 'output', 'cacheRead', 'cacheWrite', 'total'),
                ]],
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
