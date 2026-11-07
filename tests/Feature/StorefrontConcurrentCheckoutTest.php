<?php

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StorefrontOrder;
use App\Models\StorefrontStockReservation;
use App\Models\TaxRate;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function startStorefrontConcurrentCheckoutProcess(
    string $tenantId,
    string $productId,
    string $idempotencyKey,
    string $barrierDirectory,
): array {
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $command = [
        PHP_BINARY,
        'artisan',
        'storefront:test-concurrent-checkout',
        $tenantId,
        $productId,
        $idempotencyKey,
        $barrierDirectory,
        '--no-ansi',
    ];

    $environment = getenv();
    if (is_array($environment)) {
        $environment['DB_CONNECTION'] = (string) getenv('CONCURRENT_CHECKOUT_DB_CONNECTION');
        $environment['DB_HOST'] = (string) getenv('CONCURRENT_CHECKOUT_DB_HOST');
        $environment['DB_PORT'] = (string) getenv('CONCURRENT_CHECKOUT_DB_PORT');
        $environment['DB_DATABASE'] = (string) getenv('CONCURRENT_CHECKOUT_DB_DATABASE');
        $environment['DB_USERNAME'] = (string) getenv('CONCURRENT_CHECKOUT_DB_USERNAME');
        $environment['DB_PASSWORD'] = (string) getenv('CONCURRENT_CHECKOUT_DB_PASSWORD');
        $environment['APP_ENV'] = 'testing';
    } else {
        $environment = null;
    }

    $process = proc_open($command, $descriptors, $pipes, base_path(), $environment);
    expect(is_resource($process))->toBeTrue();

    fclose($pipes[0]);

    return [$process, $pipes];
}

test('separate checkout processes prevent overselling on a row-locking database', function (): void {
    if (getenv('CONCURRENT_CHECKOUT_TEST') !== '1') {
        $this->markTestSkipped('Set CONCURRENT_CHECKOUT_TEST=1 and point DB_* at a dedicated MySQL/PostgreSQL test database.');
    }

    $database = (string) getenv('CONCURRENT_CHECKOUT_DB_DATABASE');
    $driver = (string) getenv('CONCURRENT_CHECKOUT_DB_CONNECTION');
    if ($database === '' || $driver === '') {
        $this->markTestSkipped('Set CONCURRENT_CHECKOUT_DB_CONNECTION and CONCURRENT_CHECKOUT_DB_DATABASE to a dedicated row-locking test database.');
    }

    Config::set("database.connections.$driver.host", getenv('CONCURRENT_CHECKOUT_DB_HOST') ?: '127.0.0.1');
    Config::set("database.connections.$driver.port", getenv('CONCURRENT_CHECKOUT_DB_PORT') ?: '3306');
    Config::set("database.connections.$driver.database", $database);
    Config::set("database.connections.$driver.username", getenv('CONCURRENT_CHECKOUT_DB_USERNAME') ?: 'root');
    Config::set("database.connections.$driver.password", getenv('CONCURRENT_CHECKOUT_DB_PASSWORD') ?: '');
    DB::purge($driver);
    DB::setDefaultConnection($driver);

    $driver = DB::getDriverName();
    if (! in_array($driver, ['mysql', 'pgsql'], true)) {
        $this->markTestSkipped('Concurrent checkout requires MySQL or PostgreSQL row locking; SQLite is not valid proof.');
    }

    $tenant = Tenant::factory()->create(['is_active' => true]);
    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Main store',
        'is_main' => true,
        'is_active' => true,
    ]);
    TaxRate::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Sales tax',
        'rate' => 10,
        'is_active' => true,
        'is_inclusive' => false,
    ]);
    TenantSetting::factory()->create([
        'tenant_id' => $tenant->id,
        'tax_inclusive' => false,
        'default_tax_rate_id' => null,
        'is_storefront_enabled' => true,
        'bank_transfer_instructions' => null,
    ]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Last unit',
        'selling_price' => 10,
        'tax_rate_id' => null,
        'is_active' => true,
        'is_storefront_published' => true,
        'track_inventory' => true,
    ]);
    Inventory::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 1,
    ]);

    $barrierDirectory = storage_path('framework/testing/concurrency-'.Str::uuid());
    mkdir($barrierDirectory, 0777, true);

    try {
        $firstKey = Str::uuid()->toString();
        $secondKey = Str::uuid()->toString();
        [$firstProcess, $firstPipes] = startStorefrontConcurrentCheckoutProcess(
            $tenant->id,
            $product->id,
            $firstKey,
            $barrierDirectory,
        );
        [$secondProcess, $secondPipes] = startStorefrontConcurrentCheckoutProcess(
            $tenant->id,
            $product->id,
            $secondKey,
            $barrierDirectory,
        );

        $deadline = microtime(true) + 15;
        while (
            (! is_file($barrierDirectory.DIRECTORY_SEPARATOR.'ready-'.Str::slug($firstKey).'.lock')
                || ! is_file($barrierDirectory.DIRECTORY_SEPARATOR.'ready-'.Str::slug($secondKey).'.lock'))
            && microtime(true) < $deadline
        ) {
            usleep(10_000);
        }

        file_put_contents($barrierDirectory.DIRECTORY_SEPARATOR.'start.lock', 'start');

        $firstOutput = stream_get_contents($firstPipes[1]);
        $firstError = stream_get_contents($firstPipes[2]);
        $secondOutput = stream_get_contents($secondPipes[1]);
        $secondError = stream_get_contents($secondPipes[2]);
        proc_close($firstProcess);
        proc_close($secondProcess);

        $results = [
            json_decode($firstOutput, true),
            json_decode($secondOutput, true),
        ];

        if ($firstOutput === '' || $secondOutput === '') {
            throw new RuntimeException(
                implode("\n", [
                    'First process output: '.$firstOutput,
                    'First process error: '.$firstError,
                    'Second process output: '.$secondOutput,
                    'Second process error: '.$secondError,
                ]),
            );
        }

        $resultNames = array_column($results, 'result');
        sort($resultNames);

        if ($resultNames !== ['success', 'validation']) {
            throw new RuntimeException(
                implode("\n", [
                    'First process output: '.$firstOutput,
                    'First process error: '.$firstError,
                    'Second process output: '.$secondOutput,
                    'Second process error: '.$secondError,
                ]),
            );
        }

        expect($resultNames)
            ->toBe(['success', 'validation'])
            ->and(array_filter($results, fn (array $result): bool => ($result['result'] ?? null) === 'validation'))
            ->toHaveCount(1);

        expect(StorefrontOrder::query()->count())->toBe(1)
            ->and(StorefrontStockReservation::active()->count())->toBe(1);

        $activeQuantity = (float) StorefrontStockReservation::active()->sum('quantity');
        expect($activeQuantity)->toBe(1.0);
    } finally {
        foreach (glob($barrierDirectory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($barrierDirectory);
    }
});
