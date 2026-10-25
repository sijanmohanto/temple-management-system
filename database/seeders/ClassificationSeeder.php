<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;

class ClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classifications = [
            [
                'slug' => 'resident',
                'name_en' => 'Resident Devotee',
                'name_bn' => 'স্থানীয় বাসিন্দা ভক্ত',
                'description_en' => 'Permanent local resident family member within temple parish boundaries.',
                'description_bn' => 'মন্দির সংলগ্ন এলাকার স্থায়ী বাসিন্দা ভক্ত ও পরিবারবর্গ।',
                'is_system' => true,
            ],
            [
                'slug' => 'donor',
                'name_en' => 'Temple Donor / Patron',
                'name_bn' => 'মন্দির দাতা / পৃষ্ঠপোষক',
                'description_en' => 'Patron contributing regular donations, puja sponsorships, or development funds.',
                'description_bn' => 'নিয়মিত অনুদান, বিশেষ পূজা স্পনসর বা মন্দির উন্নয়ন তহবিলে সহায়তাকারী দাতা।',
                'is_system' => true,
            ],
            [
                'slug' => 'committee_member',
                'name_en' => 'Committee Member',
                'name_bn' => 'কমিটি সদস্য',
                'description_en' => 'Serving as an active or executive member in temple management bodies.',
                'description_bn' => 'মন্দির পরিচালনা বা পূজা উদযাপন কমিটির সক্রিয় সদস্য।',
                'is_system' => true,
            ],
            [
                'slug' => 'volunteer',
                'name_en' => 'Volunteer / Sevak',
                'name_bn' => 'মন্দির সেবক / স্বেচ্ছাসেবক',
                'description_en' => 'Active volunteer assisting in daily puja seva, bhog distribution, and crowd control.',
                'description_bn' => 'দৈনিক পূজা সেবা, প্রসাদ বিতরণ ও উৎসব ব্যবস্থাপনায় নিবেদিতপ্রাণ সেবক।',
                'is_system' => true,
            ],
        ];

        foreach ($classifications as $data) {
            Classification::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
