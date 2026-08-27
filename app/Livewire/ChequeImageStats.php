<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cheque;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;

#[Title('Cheque Analytics Dashboard')]
class ChequeImageStats extends Component
{
    public $filterYear = '';
    public $filterMonth = 'all';

    public function mount()
    {
        $this->filterYear = now()->format('Y');
    }

    public function getYearsProperty()
    {
        $startYear = 2024; 
        $currentYear = now()->format('Y');
        $years = [];
        for ($i = $startYear; $i <= $currentYear; $i++) {
            $years[] = $i;
        }
        return array_reverse($years);
    }

    public function getMonthsProperty()
    {
        return [
            '01' => 'January', '02' => 'February', '03' => 'March',
            '04' => 'April', '05' => 'May', '06' => 'June',
            '07' => 'July', '08' => 'August', '09' => 'September',
            '10' => 'October', '11' => 'November', '12' => 'December',
        ];
    }

    public function getStatsProperty()
    {
        $query = Cheque::query();
        
        if (!empty($this->filterYear)) {
            $query->whereYear('created_at', $this->filterYear);
        }
        
        if (!empty($this->filterMonth) && $this->filterMonth !== 'all') {
            $query->whereMonth('created_at', $this->filterMonth);
        }

        $baseQuery = clone $query;

        return [
            'total_count' => $baseQuery->count(),
            'total_amount' => (clone $baseQuery)->sum('cheque_amount'),
            'with_photo' => (clone $baseQuery)->whereNotNull('cheque_photo_url')->where('cheque_photo_url', '!=', '')->count(),
            'without_photo' => (clone $baseQuery)->where(function($q) {
                $q->whereNull('cheque_photo_url')->orWhere('cheque_photo_url', '');
            })->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'complete' => (clone $baseQuery)->where('status', 'complete')->count(),
            'return' => (clone $baseQuery)->where('status', 'return')->count(),
            'last_cheque' => (clone $baseQuery)->whereNotNull('cheque_photo_url')->where('cheque_photo_url', '!=', '')->with('customer')->orderBy('updated_at', 'desc')->first(),
        ];
    }

    public function render()
    {
        return view('livewire.cheque-image-stats', [
            'stats' => $this->stats,
            'years' => $this->years,
            'months' => $this->months
        ])->layout('components.layouts.app');
    }
}
