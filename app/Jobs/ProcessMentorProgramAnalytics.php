<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MentorProgram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job для обробки аналітики ментор-програм.
 *
 * Цей Job демонструє складність дебагінгу фонових задач,
 * де використання dd() або dump() не допомагає виявити помилку.
 *
 * Проблема: У циклі обробки даних є логічна помилка з індексацією масиву,
 * яка призводить до неправильного збереження даних в кеш.
 * Через асинхронність виконання dd()/dump() не покаже момент помилки.
 */
class ProcessMentorProgramAnalytics implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $mentorProgramId,
        public array $analyticsData = [],
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting analytics processing', [
            'mentor_program_id' => $this->mentorProgramId,
            'data_count'        => count($this->analyticsData),
        ]);

        $mentorProgram = MentorProgram::query()->findOrFail($this->mentorProgramId);

        // Симулюємо складну обробку аналітичних даних
        $processedMetrics = $this->processMetrics($mentorProgram);

        // Зберігаємо результати в кеш
        $cacheKey = 'mentor_program_analytics_'.$this->mentorProgramId;
        Cache::put($cacheKey, $processedMetrics, now()->addDay());

        Log::info('Analytics processing completed', [
            'mentor_program_id' => $this->mentorProgramId,
            'metrics_count'     => count($processedMetrics),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Analytics processing failed', [
            'mentor_program_id' => $this->mentorProgramId,
            'error'             => $exception->getMessage(),
            'trace'             => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Обробка метрик з прихованою помилкою.
     *
     * ПОМИЛКА: Логіка підрахунку використовує неправильний індекс масиву,
     * що призводить до некоректних даних.
     *
     * Через те, що Job виконується асинхронно у фоні:
     * 1. dd() призведе до зупинки worker процесу без виводу
     * 2. dump() не покаже результат, оскільки немає HTTP response
     * 3. Помилка не викликає exception, тому Job завершується успішно
     * 4. Некоректні дані тихо записуються в кеш
     *
     * Це ідеальний випадок для використання Xdebug з точками зупинки.
     */
    private function processMetrics(MentorProgram $mentorProgram): array
    {
        $metrics = [
            'total_views'     => 0,
            'engagement_rate' => 0.0,
            'conversion_rate' => 0.0,
            'daily_stats'     => [],
        ];

        // Генеруємо тестові дані за останні 7 днів
        $dailyData = [];
        for ($i = 0; $i < 7; $i++) {
            $dailyData[] = [
                'date'        => now()->subDays($i)->format('Y-m-d'),
                'views'       => random_int(10, 100),
                'clicks'      => random_int(5, 50),
                'conversions' => random_int(1, 10),
            ];
        }

        // ПОМИЛКА ТУТ: Використовується неправильний індекс при обчисленні метрик
        // Індекс $i може вийти за межі масиву, але помилка тиха
        foreach ($dailyData as $index => $data) {
            $metrics['total_views'] += $data['views'];

            // BUG: Використовується $index + 1, що може привести до undefined array key
            // При останній ітерації $index = 6, $index + 1 = 7, але масив має тільки індекси 0-6
            if (isset($dailyData[$index + 1])) {
                $previousViews = $dailyData[$index + 1]['views'];
                $growthRate = (($data['views'] - $previousViews) / $previousViews) * 100;
            } else {
                $growthRate = 0;
            }

            // BUG: Логіка розрахунку engagement_rate використовує неправильний індекс
            // Це призводить до некоректних даних, але не викликає exception
            $clicks = $data['clicks'];
            /** @phpstan-ignore greater.alwaysTrue */
            $engagementRate = $clicks > 0
                ? ($data['conversions'] / $clicks) * 100
                : 0.0;

            // BUG: Накопичення відбувається неправильно через логічну помилку
            // Використовується середнє арифметичне замість зваженого середнього
            $metrics['engagement_rate'] += $engagementRate;

            $metrics['daily_stats'][] = [
                'date'        => $data['date'],
                'views'       => $data['views'],
                'growth_rate' => $growthRate,
                'engagement'  => $engagementRate,
            ];
        }

        // BUG:Ділення на кількість днів без перевірки на нуль (хоча тут це не проблема)
        // але демонструє потенційні місця для помилок
        $dailyCount = count($dailyData);
        /** @phpstan-ignore greater.alwaysTrue */
        $metrics['engagement_rate'] = $dailyCount > 0
            ? $metrics['engagement_rate'] / $dailyCount
            : 0.0;

        // BUG: Підрахунок conversion_rate використовує некоректну формулу
        // Використовується сума конверсій / сума переглядів, але логіка неправильна
        $totalConversions = array_sum(array_column($dailyData, 'conversions'));
        $totalViews = $metrics['total_views'];
        /** @phpstan-ignore greater.alwaysTrue */
        $metrics['conversion_rate'] = $totalViews > 0
            ? ($totalConversions / $totalViews) * 100
            : 0.0;

        // Додаємо інформацію про ментор-програму
        $metrics['program_info'] = [
            'id'           => $mentorProgram->getKey(),
            'name'         => $mentorProgram->name,
            'mentor_id'    => $mentorProgram->mentor_id,
            'processed_at' => now()->toIso8601String(),
        ];

        return $metrics;
    }
}
