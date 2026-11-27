<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Manajemen Pengguna')]
class UserAndAuditManager extends Component
{
    public bool $showUserModal = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'kasir';

    public string $password = '';

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('manage-users');
    }

    public function openCreateModal(): void
    {
        $this->authorize('manage-users');
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->role = 'kasir';
        $this->password = '';
        $this->is_active = true;
        $this->resetValidation();
        $this->showUserModal = true;
    }

    public function openEditModal(int $userId): void
    {
        $this->authorize('manage-users');
        $user = User::query()->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) ($user->phone ?? '');
        $this->role = $user->role->value;
        $this->password = '';
        $this->is_active = $user->is_active;
        $this->resetValidation();
        $this->showUserModal = true;
    }

    public function saveUser(): void
    {
        $this->authorize('manage-users');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:superadmin,admin,kasir'],
            'is_active' => ['boolean'],
        ];

        if (! $this->editingUserId || $this->password !== '') {
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $validated = $this->validate($rules, [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'role' => UserRole::from($validated['role']),
            'is_active' => $validated['is_active'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->editingUserId) {
            $user = User::query()->findOrFail($this->editingUserId);
            $user->update($data);
            AuditLog::record('user.updated', $user, null, ['role' => $user->role->value, 'is_active' => $user->is_active], "Data pengguna {$user->name} diperbarui.");
            session()->flash('success', "Pengguna {$user->name} berhasil diperbarui.");
        } else {
            $user = User::query()->create($data);
            AuditLog::record('user.created', $user, null, ['role' => $user->role->value], "Pengguna baru {$user->name} dibuat.");
            session()->flash('success', "Pengguna {$user->name} berhasil ditambahkan.");
        }

        $this->showUserModal = false;
    }

    public function toggleActive(int $userId): void
    {
        $this->authorize('manage-users');
        if ($userId === Auth::id()) {
            session()->flash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');

            return;
        }

        $user = User::query()->findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);
        AuditLog::record('user.toggled', $user, null, ['is_active' => $user->is_active], "Status akun {$user->name} diubah.");
    }

    public function render()
    {
        $users = User::query()->orderBy('name')->get();

        return view('livewire.user-and-audit-manager', [
            'users' => $users,
        ]);
    }
}
