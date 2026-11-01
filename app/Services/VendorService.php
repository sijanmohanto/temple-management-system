<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use DomainException;
use Illuminate\Support\Facades\DB;

class VendorService
{
    /**
     * Create a new vendor profile.
     *
     * @param  array<string, mixed>  $data
     */
    public function createVendor(array $data, User $creator): Vendor
    {
        return DB::transaction(function () use ($data, $creator) {
            $vendorCode = Vendor::generateVendorCode();

            $vendor = Vendor::create([
                'vendor_code' => $vendorCode,
                'name' => $data['name'],
                'contact_person' => $data['contact_person'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'vendor_type' => $data['vendor_type'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);

            AuditLogger::log(
                'vendor_created',
                "Vendor {$vendor->vendor_code} ({$vendor->name}) was created.",
                $vendor,
                ['name' => $vendor->name, 'vendor_code' => $vendor->vendor_code]
            );

            return $vendor;
        });
    }

    /**
     * Update an existing vendor profile.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateVendor(Vendor $vendor, array $data, User $updater): Vendor
    {
        return DB::transaction(function () use ($vendor, $data, $updater) {
            $vendor->fill([
                'name' => $data['name'],
                'contact_person' => $data['contact_person'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'vendor_type' => $data['vendor_type'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'updated_by' => $updater->id,
            ]);

            $changes = $vendor->getDirty();
            $vendor->save();

            AuditLogger::log(
                'vendor_updated',
                "Vendor {$vendor->vendor_code} ({$vendor->name}) was updated.",
                $vendor,
                ['changes' => $changes]
            );

            return $vendor;
        });
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleActiveStatus(Vendor $vendor, User $user): Vendor
    {
        $vendor->is_active = ! $vendor->is_active;
        $vendor->updated_by = $user->id;
        $vendor->save();

        $statusStr = $vendor->is_active ? 'active' : 'inactive';
        AuditLogger::log(
            'vendor_status_toggled',
            "Vendor {$vendor->vendor_code} status set to {$statusStr}.",
            $vendor,
            ['is_active' => $vendor->is_active]
        );

        return $vendor;
    }

    /**
     * Delete vendor if no protected transactions exist.
     *
     * @throws DomainException
     */
    public function deleteVendor(Vendor $vendor, User $user): bool
    {
        if ($vendor->hasProtectedTransactions()) {
            throw new DomainException(__('procurement.cannot_delete_vendor_with_transactions'));
        }

        $vendor->updated_by = $user->id;
        $vendor->save();

        AuditLogger::log(
            'vendor_deleted',
            "Vendor {$vendor->vendor_code} was soft-deleted.",
            $vendor
        );

        return (bool) $vendor->delete();
    }

    /**
     * Search active vendors formatted for Select2 AJAX queries.
     *
     * @return array{results: array<int, array<string, mixed>>}
     */
    public function searchVendors(string $term, ?string $type = null, bool $onlyActive = true, int $limit = 20): array
    {
        $query = Vendor::query();

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        if (! empty($type)) {
            $query->where('vendor_type', $type);
        }

        if (trim($term) !== '') {
            $searchTerm = trim($term);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('vendor_code', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%")
                    ->orWhere('contact_person', 'like', "%{$searchTerm}%");
            });
        }

        $vendors = $query->orderBy('name')->limit($limit)->get();

        return [
            'results' => $vendors->map(fn (Vendor $v) => [
                'id' => $v->id,
                'text' => $v->name.' ('.$v->vendor_code.')'.($v->phone ? ' - '.$v->phone : ''),
                'name' => $v->name,
                'vendor_code' => $v->vendor_code,
                'phone' => $v->phone,
                'vendor_type' => $v->vendor_type,
                'vendor_type_label' => $v->vendor_type_label,
            ])->values()->all(),
        ];
    }
}
