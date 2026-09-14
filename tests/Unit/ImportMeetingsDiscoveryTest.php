<?php

namespace Tests\Unit;

use App\Console\Commands\ImportMeetings;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class ImportMeetingsDiscoveryTest extends TestCase
{
    #[Test]
    public function it_discovers_legacy_and_completed_isolated_sessions_only(): void
    {
        $dir = storage_path('framework/testing/meetings-'.uniqid());
        mkdir($dir, 0777, true);
        touch($dir.'/20260901_100000_aaaaaaaa_part000.wav');
        mkdir($dir.'/20260901_110000_bbbbbbbb');
        touch($dir.'/20260901_110000_bbbbbbbb/COMPLETADA');
        mkdir($dir.'/20260901_120000_cccccccc');

        try {
            $method = new ReflectionMethod(ImportMeetings::class, 'descubrirBases');
            $bases = $method->invoke(new ImportMeetings, $dir);

            $this->assertSame([
                '20260901_100000_aaaaaaaa',
                '20260901_110000_bbbbbbbb',
            ], $bases);
        } finally {
            (new Filesystem)->deleteDirectory($dir);
        }
    }
}
