<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_code_generation_format(): void
    {
        $code1 = Person::generatePersonCode();
        $this->assertMatchesRegularExpression('/^DEV-\d{5}$/', $code1);
        $this->assertEquals('DEV-00001', $code1);

        Person::factory()->create(['person_code' => $code1]);

        $code2 = Person::generatePersonCode();
        $this->assertEquals('DEV-00002', $code2);
    }

    public function test_family_code_generation_format(): void
    {
        $code1 = Family::generateFamilyCode();
        $this->assertMatchesRegularExpression('/^FAM-\d{4}$/', $code1);
        $this->assertEquals('FAM-0001', $code1);

        Family::factory()->create(['family_code' => $code1]);

        $code2 = Family::generateFamilyCode();
        $this->assertEquals('FAM-0002', $code2);
    }
}
