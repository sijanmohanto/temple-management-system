<?php

namespace Tests\Unit;

use App\Models\Person;
use App\Services\DuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_duplicate_phone(): void
    {
        $existing = Person::factory()->create([
            'name_en' => 'Subrata Majumder',
            'phone' => '+8801712000222',
            'email' => 'subrata@example.com',
        ]);

        $service = new DuplicateDetectionService;

        // Matching phone
        $result = $service->findDuplicates([
            'name_en' => 'Different Person',
            'phone' => '01712000222',
        ]);

        $this->assertTrue($result['has_duplicates']);
        $this->assertCount(1, $result['matches']);
        $this->assertEquals($existing->id, $result['matches'][0]['person']->id);
    }

    public function test_detects_duplicate_email(): void
    {
        $existing = Person::factory()->create([
            'name_en' => 'Ananya Chatterjee',
            'email' => 'ananya@temple.local',
        ]);

        $service = new DuplicateDetectionService;

        $result = $service->findDuplicates([
            'name_en' => 'Another Name',
            'email' => 'ANANYA@temple.local',
        ]);

        $this->assertTrue($result['has_duplicates']);
        $this->assertEquals($existing->id, $result['matches'][0]['person']->id);
    }

    public function test_detects_duplicate_name_and_dob(): void
    {
        $existing = Person::factory()->create([
            'name_en' => 'Dipankar Chatterjee',
            'date_of_birth' => '1970-04-15',
        ]);

        $service = new DuplicateDetectionService;

        $result = $service->findDuplicates([
            'name_en' => 'Dipankar Chatterjee',
            'date_of_birth' => '1970-04-15',
        ]);

        $this->assertTrue($result['has_duplicates']);
        $this->assertEquals($existing->id, $result['matches'][0]['person']->id);
    }

    public function test_excludes_self_during_update(): void
    {
        $person = Person::factory()->create([
            'name_en' => 'Dipankar Chatterjee',
            'phone' => '01711000111',
            'email' => 'dipankar@temple.local',
        ]);

        $service = new DuplicateDetectionService;

        $result = $service->findDuplicates([
            'name_en' => 'Dipankar Chatterjee',
            'phone' => '01711000111',
            'email' => 'dipankar@temple.local',
        ], $person->id);

        $this->assertFalse($result['has_duplicates']);
        $this->assertEmpty($result['matches']);
    }
}
