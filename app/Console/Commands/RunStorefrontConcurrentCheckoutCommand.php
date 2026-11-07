<?php

namespace App\Console\Commands;

use App\Actions\Storefront\CreateStorefrontOrderAction;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RunStorefrontConcurrentCheckoutCommand extends Command
{
    protected $signature = 'storefront:test-concurrent-checkout
        {tenant_id : Tenant ID}
        {product_id : Product ID}
        {idempotency_key : Checkout idempotency key}
        {barrier_dir : Directory used to synchronize processes}';

    protected $description = 'Run one checkout process for production-like concurrent stock testing';

    public function handle(CreateStorefrontOrderAction $createOrder): int
    {
        $tenant = Tenant::query()->with('tenantSetting')->findOrFail($this->argument('tenant_id'));
        $barrierDirectory = rtrim((string) $this->argument('barrier_dir'), DIRECTORY_SEPARATOR);
        $idempotencyKey = (string) $this->argument('idempotency_key');
        $readyFile = $barrierDirectory.DIRECTORY_SEPARATOR.'ready-'.Str::slug($idempotencyKey).'.lock';
        $startFile = $barrierDirectory.DIRECTORY_SEPARATOR.'start.lock';

        file_put_contents($readyFile, 'ready');

        $deadline = microtime(true) + 15;
        while (! is_file($startFile) && microtime(true) < $deadline) {
            usleep(10_000);
        }

        if (! is_file($startFile)) {
            $this->output->writeln(json_encode([
                'result' => 'barrier_timeout',
            ]));

            return 1;
        }

        try {
            $order = $createOrder->execute(
                tenant: $tenant,
                customerData: [
                    'customer_name' => 'Concurrency Shopper',
                    'customer_phone' => '555-0101',
                    'customer_email' => null,
                    'fulfillment_method' => 'pickup',
                    'payment_method' => 'cash',
                    'idempotency_key' => $idempotencyKey,
                ],
                cart: [[
                    'product_id' => (string) $this->argument('product_id'),
                    'variant_id' => null,
                    'quantity' => 1,
                ]],
                customer: null,
                trackingToken: Str::random(64),
            );

            $this->output->writeln(json_encode([
                'result' => 'success',
                'order_id' => $order->id,
            ]));

            return 0;
        } catch (ValidationException $exception) {
            $this->output->writeln(json_encode([
                'result' => 'validation',
                'errors' => $exception->errors(),
            ]));

            return 0;
        } catch (Throwable $exception) {
            $this->output->writeln(json_encode([
                'result' => 'error',
                'class' => $exception::class,
                'message' => $exception->getMessage(),
            ]));

            return 1;
        }
    }
}
