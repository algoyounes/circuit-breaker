<?php

namespace AlgoYounes\CircuitBreaker\Commands;

use AlgoYounes\CircuitBreaker\Config\CircuitBreakerConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;

class CircuitListCommand extends Command
{
    protected $signature = 'circuit:list 
                            {--service= : Filter by service name}
                            {--silent : Suppress output formatting}';

    protected $description = 'List all circuit breaker states';

    public function handle(
        CircuitBreakerConfig $config,
        StateManagerContract $stateManager
    ): void
    {
        $services = $this->option('service')
            ? [$this->option('service')]
            : array_keys($config->getServices());

        // 

        if (empty($services)) {
            $this->error('No services configured');

            return;
        }

        $rows = [];
        foreach ($services as $service) {
            if (! $stateManager->hasState($service)) {
                continue;
            }

            $rows[] = [
                'service'        => $service,
                'status'         => $stateManager->getStatus($service)->value,
                'failures'       => $this->formatCount(
                    $stateManager->getFailureCount($service),
                    config("circuit-breaker.services.$service.failure_threshold",
                        config('circuit-breaker.defaults.failure_threshold'))
                ),
                'successes'      => $this->formatCount(
                    $stateManager->getSuccessCount($service),
                    config("circuit-breaker.services.$service.success_threshold",
                        config('circuit-breaker.defaults.success_threshold'))
                ),
                'cooldown_ends'  => $this->formatCooldown(
                    $stateManager->getCooldownEndTime($service)
                ),
            ];
        }

        $this->table(
            ['Service', 'Status', 'Failures', 'Successes', 'Cooldown Ends'],
            $rows
        );
    }

    private function formatCount(int $current, int $threshold): string
    {
        return "{$current}/{$threshold}";
    }

    private function formatCooldown(?int $timestamp): string
    {
        return $timestamp
            ? Carbon::createFromTimestamp($timestamp)->toDateTimeString()
            : '-';
    }
}
