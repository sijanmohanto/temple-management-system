<?php

namespace App\Services;

use App\Models\Person;

class DuplicateDetectionService
{
    /**
     * Check for potential duplicate people profiles given an input array.
     * Optionally exclude an existing person ID during updates.
     *
     * @return array{has_duplicates: bool, matches: array<int, array{person: Person, reasons: array<int, string>}>}
     */
    public function findDuplicates(array $data, ?int $excludePersonId = null): array
    {
        $matches = [];
        $candidates = collect();

        $phone = ! empty($data['phone']) ? preg_replace('/[^\d+]/', '', (string) $data['phone']) : null;
        $email = ! empty($data['email']) ? strtolower(trim((string) $data['email'])) : null;
        $nameEn = ! empty($data['name_en']) ? trim((string) $data['name_en']) : null;
        $nameBn = ! empty($data['name_bn']) ? trim((string) $data['name_bn']) : null;
        $dob = ! empty($data['date_of_birth']) ? trim((string) $data['date_of_birth']) : null;
        $fatherName = ! empty($data['father_name']) ? trim((string) $data['father_name']) : null;

        // Query candidates by phone or email or name
        $query = Person::query();
        if ($excludePersonId) {
            $query->where('id', '!=', $excludePersonId);
        }

        $query->where(function ($q) use ($phone, $email, $nameEn, $nameBn) {
            $hasCondition = false;
            if ($phone) {
                $q->where('phone', 'like', "%{$phone}%");
                $hasCondition = true;
            }
            if ($email) {
                if ($hasCondition) {
                    $q->orWhere('email', $email);
                } else {
                    $q->where('email', $email);
                    $hasCondition = true;
                }
            }
            if ($nameEn) {
                if ($hasCondition) {
                    $q->orWhere('name_en', 'like', "%{$nameEn}%");
                } else {
                    $q->where('name_en', 'like', "%{$nameEn}%");
                    $hasCondition = true;
                }
            }
            if ($nameBn) {
                if ($hasCondition) {
                    $q->orWhere('name_bn', 'like', "%{$nameBn}%");
                } else {
                    $q->where('name_bn', 'like', "%{$nameBn}%");
                    $hasCondition = true;
                }
            }
        });

        $candidateRecords = $query->with('family', 'classifications')->limit(15)->get();

        foreach ($candidateRecords as $person) {
            $reasons = [];

            // Check phone
            if ($phone && ! empty($person->phone)) {
                $personPhone = preg_replace('/[^\d+]/', '', $person->phone);
                if ($phone === $personPhone || str_contains($personPhone, $phone) || str_contains($phone, $personPhone)) {
                    $reasons[] = __('people.duplicate_phone_match', ['phone' => $person->phone]);
                }
            }

            // Check email
            if ($email && ! empty($person->email)) {
                if (strtolower(trim($person->email)) === $email) {
                    $reasons[] = __('people.duplicate_email_match', ['email' => $person->email]);
                }
            }

            // Check name combinations
            $nameMatches = false;
            if ($nameEn && strcasecmp(trim($person->name_en), $nameEn) === 0) {
                $nameMatches = true;
            }
            if ($nameBn && ! empty($person->name_bn) && trim($person->name_bn) === $nameBn) {
                $nameMatches = true;
            }

            if ($nameMatches) {
                if ($dob && $person->date_of_birth && $person->date_of_birth->toDateString() === $dob) {
                    $reasons[] = __('people.duplicate_name_dob_match');
                } elseif ($fatherName && ! empty($person->father_name) && strcasecmp(trim($person->father_name), $fatherName) === 0) {
                    $reasons[] = __('people.duplicate_name_father_match');
                } else {
                    $reasons[] = __('people.duplicate_name_match', ['name' => $person->name]);
                }
            }

            if (! empty($reasons)) {
                $matches[] = [
                    'person' => $person,
                    'reasons' => $reasons,
                ];
            }
        }

        return [
            'has_duplicates' => ! empty($matches),
            'matches' => $matches,
        ];
    }
}
