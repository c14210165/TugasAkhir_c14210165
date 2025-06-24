<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Riwayatpeminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class riwayatpeminjamanController extends Controller
{
    public function index()
    {
        $user = request()->user();

        if ($user->role === 'Siswa') {
            $riwayat = Riwayatpeminjaman::with(['peminjaman.permohonan.user', 'peminjaman.barang'])
                ->whereHas('peminjaman.permohonan', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->get();
        } else {
            $riwayat = Riwayatpeminjaman::with(['peminjaman.permohonan.user', 'peminjaman.barang'])->get();
        }

        return response()->json($riwayat);
    }

    public function getDataHarian()
    {
        $data = DB::table('riwayatpeminjamans')
            ->select('tanggal_pinjam', DB::raw('count(*) as total_pinjam'))
            ->groupBy('tanggal_pinjam')
            ->orderBy('tanggal_pinjam')
            ->pluck('total_pinjam', 'tanggal_pinjam')
            ->toArray();

        return $data;
    }

    public function getDataMingguan()
    {
        $data = DB::table('riwayatpeminjamans')
        ->select(
            DB::raw('YEAR(tanggal_pinjam) as tahun'),
            DB::raw('WEEK(tanggal_pinjam, 1) as minggu'),
            DB::raw('COUNT(*) as total_pinjam')
        )
        ->groupBy('tahun', 'minggu')
        ->orderBy('tahun')
        ->orderBy('minggu')
        ->get();

        $result = [];
        foreach ($data as $row) {
            $key = $row->tahun . '-W' . str_pad($row->minggu, 2, '0', STR_PAD_LEFT);
            $result[$key] = $row->total_pinjam;
        }

        return $result;
    }

    public function getDataBulanan()
    {
        $data = DB::table('riwayatpeminjamans')
            ->select(
                DB::raw('YEAR(tanggal_pinjam) as tahun'),
                DB::raw('MONTH(tanggal_pinjam) as bulan'),
                DB::raw('COUNT(*) as total_pinjam')
            )
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get();

        $result = [];
        foreach ($data as $row) {
            // Format bulan dengan 2 digit, misal 2025-01, 2025-12
            $key = $row->tahun . '-' . str_pad($row->bulan, 2, '0', STR_PAD_LEFT);
            $result[$key] = $row->total_pinjam;
        }

        return $result;
    }

    private function holtWintersAdditive(array $data, int $period, int $forecastLength, float $alpha, float $beta, float $gamma, string $intervalUnit = 'day')
    {
        $result = [];
        $n = count($data);
        $dates = array_keys($data);
        $values = array_values($data);

        if ($n < 2 * $period) {
            return ['error' => 'Data tidak cukup untuk prediksi'];
        }

        // Initialize level, trend, seasonality arrays
        $level = $values[0];
        $trend = $values[$period] - $values[0];
        $seasonals = [];

        // Initialize seasonal components
        for ($i = 0; $i < $period; $i++) {
            $seasonals[$i] = $values[$i] - $level;
        }

        for ($i = 0; $i < $n; $i++) {
            $season = $seasonals[$i % $period];
            $lastLevel = $level;
            $level = $alpha * ($values[$i] - $season) + (1 - $alpha) * ($level + $trend);
            $trend = $beta * ($level - $lastLevel) + (1 - $beta) * $trend;
            $seasonals[$i % $period] = $gamma * ($values[$i] - $level - $trend) + (1 - $gamma) * $season;
            $result[$dates[$i]] = $level + $trend + $seasonals[$i % $period];
        }

        // Forecast future points
        $forecastOnly = [];

        for ($i = $n; $i < $n + $forecastLength; $i++) {
            $lastDate = Carbon::parse($dates[$n - 1]);

            switch ($intervalUnit) {
                case 'week':
                    $forecastDate = $lastDate->copy()->addWeeks($i - $n + 1)->format('Y-m-d');
                    break;
                case 'month':
                    $forecastDate = $lastDate->copy()->addMonths($i - $n + 1)->format('Y-m');
                    break;
                default: // daily
                    $forecastDate = $lastDate->copy()->addDays($i - $n + 1)->format('Y-m-d');
                    break;
            }

            $forecastOnly[$forecastDate] = $level + ($i - $n + 1) * $trend + $seasonals[$i % $period];
        }

        // Kembalikan hanya forecast
        return $forecastOnly;
    }

    public function prediksiHarian()
    {
        $data = $this->getDataHarian();

        if (isset($data['error'])) {
            return response()->json($data, 400);
        }

        $alpha = 0.4;
        $beta = 0.2;
        $gamma = 0.3;
        $period = 7; // pola musiman harian = 7 hari
        $forecastLength = 7; // prediksi 7 hari ke depan

        $prediction = $this->holtWintersAdditive($data, $period, $forecastLength, $alpha, $beta, $gamma, 'day');

        return response()->json($prediction);
    }

    public function prediksiMingguan()
    {
        $data = $this->getDataMingguan();

        if (isset($data['error'])) {
            return response()->json($data, 400);
        }

        // Parameter alpha, beta, gamma untuk mingguan bisa diatur moderate
        $alpha = 0.3;
        $beta = 0.1;
        $gamma = 0.2;
        $period = 13; // pola musiman setahun = 52 minggu
        $forecastLength = 6; // prediksi 7 minggu ke depan

        $prediction = $this->holtWintersAdditive($data, $period, $forecastLength, $alpha, $beta, $gamma, 'week');

        return response()->json($prediction);
    }

    public function prediksiBulanan()
    {
        $data = $this->getDataBulanan();

        if (isset($data['error'])) {
            return response()->json($data, 400);
        }

        // Parameter alpha, beta, gamma untuk bulanan, agak lebih smooth
        $alpha = 0.2;
        $beta = 0.1;
        $gamma = 0.1;
        $period = 6; // pola musiman setahun = 12 bulan
        $forecastLength = 6; // prediksi 6 bulan ke depan

        $prediction = $this->holtWintersAdditive($data, $period, $forecastLength, $alpha, $beta, $gamma, 'month');

        return response()->json($prediction);
    }

}
