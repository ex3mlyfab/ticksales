<?php

namespace App\Console\Commands;

use App\Actions\Storefront\EraseStorefrontCustomerDataAction;
use App\Models\StorefrontOrder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('storefront:purge-customer-data')]
#[Description('Erase personal data from terminal storefront orders after the retention period')]
class StorefrontPurgeCustomerDataCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(EraseStorefrontCustomerDataAction $eraseAction): int
    {
        $days = max(1, (int) config('storefront.customer_data_retention_days', 365));
        $cutoff = now()->subDays($days);
        $purged = 0;

        StorefrontOrder::query()
            ->whereIn('status', ['completed', 'cancelled'])
            ->whereNull('personal_data_erased_at')
            ->where('updated_at', '<=', $cutoff)
            ->with('tenant')
            ->chunkById(100, function ($orders) use ($eraseAction, &$purged): void {
                foreach ($orders as $order) {
                    if ($order->tenant !== null) {
                        $eraseAction->erase($order->tenant, $order);
                        $purged++;
                    }
                }
            });

        $this->info("Purged customer data from {$purged} storefront order(s).");

        return self::SUCCESS;
    }
}
