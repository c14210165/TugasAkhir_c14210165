<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loan;
use App\Enums\LoanStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;

class PredictionController extends Controller
{
    public function generate(string $itemType)
    {
        // Menjalankan prediksi untuk setiap interval dengan panjang forecast yang sudah ditentukan
        $dailyPrediction = $this->runPrediction($itemType, 'daily', 7);
        $weeklyPrediction = $this->runPrediction($itemType, 'weekly', 4);
        $monthlyPrediction = $this->runPrediction($itemType, 'monthly', 3);

        // Mengemas semua hasil prediksi ke dalam satu response
        return response()->json([
            'daily' => $dailyPrediction,
            'weekly' => $weeklyPrediction,
            'monthly' => $monthlyPrediction,
        ]);
    }

    /**
     * Fungsi utama yang membungkus proses pengambilan data dan prediksi.
     */
    private function runPrediction(string $itemType, string $interval, int $forecastLength): array
    {
        $timeSeriesData = $this->prepareTimeSeriesData($itemType, $interval);
        $params = $this->getHoltWintersParams($interval);

        // Cek jika data historis cukup untuk membuat prediksi
        if (count($timeSeriesData) < (2 * $params['period'])) {
            return [
                'error' => "Data historis tidak cukup untuk prediksi " . $interval,
                'prediction_total' => 0
            ];
        }

        $prediction = $this->holtWinters(
            $timeSeriesData,
            $params['period'],
            $forecastLength,
            $params['alpha'],
            $params['beta'],
            $params['gamma']
        );

        // Kita hanya butuh totalnya di frontend
        return [
            'prediction_total' => array_sum($prediction)
        ];
    }

    /**
     * Mengambil & menyiapkan data time series dari database.
     */
    private function prepareTimeSeriesData(string $itemType, string $interval): array
    {
        $format = ''; $groupByRaw = ''; $periodUnit = '';
        switch ($interval) {
            case 'monthly':
                $format = 'Y-m'; $groupByRaw = "DATE_FORMAT(created_at, '%Y-%m')"; $periodUnit = '1 month'; break;
            case 'weekly':
                $format = 'Y-W'; $groupByRaw = "DATE_FORMAT(created_at, '%Y-%v')"; $periodUnit = '1 week'; break;
            default: // daily
                $format = 'Y-m-d'; $groupByRaw = 'DATE(created_at)'; $periodUnit = '1 day'; break;
        }
        
        $counts = Loan::where('item_type', $itemType)->where('status', LoanStatus::COMPLETED)
            ->selectRaw("{$groupByRaw} as date, COUNT(*) as total")
            ->groupByRaw($groupByRaw)->orderBy('date', 'asc')->get()->keyBy('date');

        if ($counts->isEmpty()) return [];

        // === BAGIAN PERBAIKAN UTAMA ADA DI SINI ===
        $firstDateKey = $counts->keys()->first();
        $lastDateKey = $counts->keys()->last();
        $startDate = null;
        $endDate = null;

        if ($interval === 'weekly') {
            // Jika mingguan, kita perlu parsing manual format 'YYYY-WW'
            [$startYear, $startWeek] = explode('-', $firstDateKey);
            $startDate = Carbon::now()->setISODate($startYear, $startWeek)->startOfWeek();

            [$endYear, $endWeek] = explode('-', $lastDateKey);
            $endDate = Carbon::now()->setISODate($endYear, $endWeek)->endOfWeek();
        } else {
            // Untuk harian dan bulanan, parsing biasa sudah cukup
            $startDate = Carbon::parse($firstDateKey);
            $endDate = Carbon::parse($lastDateKey);
        }
        // === AKHIR BAGIAN PERBAIKAN ===

        $period = CarbonPeriod::create($startDate, $periodUnit, $endDate);

        $timeSeries = [];
        foreach ($period as $date) {
            $key = $date->format($format);
            $timeSeries[$key] = $counts->get($key)->total ?? 0;
        }
        return $timeSeries;
    }
    
    /**
     * Implementasi algoritma Holt-Winters Additive.
     */
    private function holtWinters(array $data, int $period, int $forecastLength, float $alpha, float $beta, float $gamma): array
    {
        $values = array_values($data);
        $n = count($values);

        $level = $values[0];
        $trend = 0;
        for($i = 0; $i < $period; $i++){
            if (isset($values[$i+$period])) {
                $trend += ($values[$i+$period] - $values[$i]) / $period;
            }
        }
        $trend /= $period;
        $seasonals = array_slice($values, 0, $period);

        for ($i = $period; $i < $n; $i++) {
            $lastLevel = $level;
            $season = $seasonals[$i - $period];
            $level = $alpha * ($values[$i] - $season) + (1 - $alpha) * ($level + $trend);
            $trend = $beta * ($level - $lastLevel) + (1 - $beta) * $trend;
            $seasonals[$i] = $gamma * ($values[$i] - $level) + (1 - $gamma) * $season;
        }

        $forecast = [];
        for ($i = 0; $i < $forecastLength; $i++) {
            $seasonalIndex = ($n - $period) + ($i % $period);
            $forecastValue = $level + ($i + 1) * $trend + ($seasonals[$seasonalIndex] ?? 0);
            $forecast[] = max(0, round($forecastValue));
        }
        return $forecast;
    }

    /**
     * Helper untuk parameter Holt-Winters.
     */
    private function getHoltWintersParams(string $interval): array
    {
        if ($interval === 'weekly') return ['period' => 4, 'alpha' => 0.3, 'beta' => 0.1, 'gamma' => 0.2];
        if ($interval === 'monthly') return ['period' => 12, 'alpha' => 0.2, 'beta' => 0.1, 'gamma' => 0.1];
        return ['period' => 7, 'alpha' => 0.4, 'beta' => 0.2, 'gamma' => 0.3];
    }
}
