<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\StoreSetting;
use App\Services\PaymentGateway\PaymentGatewayManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pengaturan Toko')]
class StoreSettingsManager extends Component
{
    public string $store_name = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $receipt_footer = '';

    public int $low_stock_threshold = 5;

    public bool $qris_enabled = true;

    public function mount(): void
    {
        $this->authorize('manage-settings');
        $setting = StoreSetting::current();

        $this->store_name = $setting->store_name;
        $this->address = (string) ($setting->address ?? '');
        $this->phone = (string) ($setting->phone ?? '');
        $this->email = (string) ($setting->email ?? '');
        $this->receipt_footer = (string) ($setting->receipt_footer ?? '');
        $this->low_stock_threshold = $setting->low_stock_threshold;
        $this->qris_enabled = $setting->qris_enabled;
    }

    public function save(): void
    {
        $this->authorize('manage-settings');

        $validated = $this->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'qris_enabled' => ['boolean'],
        ], [
            'store_name.required' => 'Nama toko wajib diisi.',
        ]);

        $setting = StoreSetting::current();
        $setting->update($validated);

        AuditLog::record('settings.updated', $setting, null, $validated, 'Pengaturan toko diperbarui.');

        session()->flash('success', 'Pengaturan toko berhasil disimpan.');
    }

    public function render(PaymentGatewayManager $gatewayManager)
    {
        return view('livewire.store-settings-manager', [
            'qrisConfigured' => $gatewayManager->isQrisConfigured(),
            'gatewayProvider' => strtoupper((string) config('payment.provider')),
            'gatewayMode' => strtoupper((string) config('payment.mode')),
        ]);
    }
}
