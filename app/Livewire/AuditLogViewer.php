<?php

namespace App\Livewire;

use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Audit Log')]
class AuditLogViewer extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterAction = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorize('view-audit-logs');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAction(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $auditLogs = AuditLog::query()
            ->with('user')
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('description', 'like', $term)
                        ->orWhere('action', 'like', $term)
                        ->orWhere('ip_address', 'like', $term)
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $term));
                });
            })
            ->when($this->filterAction !== '', fn ($q) => $q->where('action', $this->filterAction))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(20);

        return view('livewire.audit-log-viewer', [
            'auditLogs' => $auditLogs,
            'actions' => $actions,
        ]);
    }
}
