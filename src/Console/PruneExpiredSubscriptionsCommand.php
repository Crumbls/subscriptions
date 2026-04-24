<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Console;

use Illuminate\Console\Command;

class PruneExpiredSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:prune
        {--days=30 : Delete subscriptions ended more than this many days ago}
        {--force : Skip confirmation}';

    protected $description = 'Soft-delete expired subscriptions and their usage records';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $model = config('subscriptions.models.plan_subscription');

        $query = fn () => $model::where('ends_at', '<=', now()->subDays($days))
            ->whereNull('deleted_at');

        $count = $query()->count();

        if ($count === 0) {
            $this->info('No expired subscriptions to prune.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("This will soft-delete {$count} expired subscription(s). Continue?")) {
            return self::SUCCESS;
        }

        $query()->each(fn ($sub) => $sub->delete());

        $this->info("Pruned {$count} expired subscription(s).");

        return self::SUCCESS;
    }
}
