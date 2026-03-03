<?php

namespace App\Filament\Pages;

use App\Models\Territory;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class TerritoryMapPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Territory Map';
    protected static ?string $title = 'Global Territory Map';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.territory-map';

    public function getViewData(): array
    {
        $territories = Territory::with('user')
            ->where('is_active', true)
            ->get()
            ->map(fn ($t) => [
                'id'       => $t->id,
                'polygon'  => $t->polygon,
                'color'    => $t->color ?? '#3388FF',
                'userName' => $t->user?->name ?? 'Unknown',
                'area'     => round($t->area, 2),
                'date'     => $t->created_at?->format('Y-m-d'),
            ]);

        // Compute daily totals for the growth slider
        $dailyTotals = Territory::where('is_active', true)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'), DB::raw('SUM(area) as total_area'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'territoriesJson' => $territories->toJson(),
            'dailyTotalsJson' => $dailyTotals->toJson(),
            'mapsApiKey'      => config('services.google_maps.api_key', ''),
        ];
    }
}
