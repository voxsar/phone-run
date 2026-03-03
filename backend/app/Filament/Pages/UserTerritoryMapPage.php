<?php

namespace App\Filament\Pages;

use App\Models\Territory;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class UserTerritoryMapPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.user-territory-map';

    /** Populated from the ?user_id= query parameter in mount(). */
    public int $userId = 0;

    public function mount(): void
    {
        $this->userId = (int) request()->query('user_id', 0);
        abort_if($this->userId === 0, 404);
    }

    public function getTitle(): string
    {
        $user = User::find($this->userId);
        return ($user?->name ?? 'Unknown') . "'s Territories";
    }

    public function getViewData(): array
    {
        $user = User::findOrFail($this->userId);

        $territories = Territory::where('user_id', $this->userId)
            ->where('is_active', true)
            ->get()
            ->map(fn ($t) => [
                'id'      => $t->id,
                'polygon' => $t->polygon,
                'color'   => $t->color ?? '#3388FF',
                'area'    => round($t->area, 2),
                'date'    => $t->created_at?->format('Y-m-d'),
            ]);

        $dailyTotals = Territory::where('user_id', $this->userId)
            ->where('is_active', true)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'), DB::raw('SUM(area) as total_area'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'user'            => $user,
            'territoriesJson' => $territories->toJson(),
            'dailyTotalsJson' => $dailyTotals->toJson(),
            'mapsApiKey'      => config('services.google_maps.api_key', ''),
        ];
    }
}
