<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class EventCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'durga-puja',
                'name_en' => 'Durga Puja',
                'name_bn' => 'শ্রীশ্রী দুর্গাপূজা',
                'description_en' => 'Annual autumnal worship of Goddess Durga (Sharadiya Utsav) spanning Mahalaya to Dashami.',
                'description_bn' => 'মহালয়া থেকে বিজয়া দশমী পর্যন্ত শারদীয় দুর্গাপূজা মহোৎসব।',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'slug' => 'lakshmi-puja',
                'name_en' => 'Lakshmi Puja',
                'name_bn' => 'শ্রীশ্রী লক্ষ্মীপূজা',
                'description_en' => 'Kojagari Lakshmi Puja celebrated on the full moon night following Durga Puja.',
                'description_bn' => 'শারদীয় দুর্গোৎসবের পর আশ্বিন পূর্ণিমা তিথিতে কোজাগরী লক্ষ্মীপূজা।',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'slug' => 'kali-puja',
                'name_en' => 'Kali Puja / Shyama Puja',
                'name_bn' => 'শ্রীশ্রী কালীপূজা / শ্যামাপূজা',
                'description_en' => 'Dipanwita worship of Goddess Shyama / Kali on the new moon of Kartik.',
                'description_bn' => 'কার্তিক অমাবস্যা তিথিতে দীপান্বিতা কালীপূজা ও দীপাবলি উৎসব।',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'slug' => 'saraswati-puja',
                'name_en' => 'Saraswati Puja',
                'name_bn' => 'শ্রীশ্রী সরস্বতীপূজা',
                'description_en' => 'Vasant Panchami worship of the Goddess of Learning, Arts and Music.',
                'description_bn' => 'বসন্ত পঞ্চমীতে বিদ্যা ও সঙ্গীতদেবী মা সরস্বতীর আরাধনা ও হাতেখড়ি।',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'slug' => 'maha-shivaratri',
                'name_en' => 'Maha Shivaratri',
                'name_bn' => 'মহা শিবরাত্রি ব্রত',
                'description_en' => 'Solemn religious festival dedicated to the devotion of Lord Shiva with four-prahar worship.',
                'description_bn' => 'ভগবান শিবের আরাধনায় চার প্রহরব্যাপী শিবরাত্রি ব্রত ও বিশেষ রুদ্রাভিষেক।',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'slug' => 'janmashtami',
                'name_en' => 'Shree Krishna Janmashtami',
                'name_bn' => 'শ্রীকৃষ্ণ জন্মাষ্টমী মহোৎসব',
                'description_en' => 'Celebration of the birth of Lord Sri Krishna with midnight aarti, kirtan and prasad.',
                'description_bn' => 'ভাদ্র অষ্টমী তিথিতে পরমেশ্বর শ্রীকৃষ্ণের আবির্ভাব তিথি মহোৎসব।',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'slug' => 'dol-yatra',
                'name_en' => 'Dol Yatra / Holi',
                'name_bn' => 'দোলযাত্রা ও গৌর পূর্ণিমা',
                'description_en' => 'Spring festival of colors and the appearance day of Sri Chaitanya Mahaprabhu.',
                'description_bn' => 'বসন্ত ঋতুতে শ্রীশ্রী রাধাকৃষ্ণের দোল উৎসব ও শ্রীমন্মহাপ্রভুর শুভ আবির্ভাব।',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'slug' => 'annapurna-puja',
                'name_en' => 'Annapurna Puja',
                'name_bn' => 'শ্রীশ্রী অন্নপূর্ণা পূজা',
                'description_en' => 'Chaitra Shukla Ashtami worship of Goddess Annapurna for abundance and food security.',
                'description_bn' => 'চৈত্রী শুক্লা অষ্টমী তিথিতে জগতের অন্নদাত্রী মা অন্নপূর্ণার বিশেষ পূজা।',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'slug' => 'cultural-community',
                'name_en' => 'Cultural & Community Event',
                'name_bn' => 'সাংস্কৃতিক ও সামাজিক অনুষ্ঠান',
                'description_en' => 'Temple community gatherings, Bengali New Year (Pohela Boishakh), musical programs and seminars.',
                'description_bn' => 'পহেলা বৈশাখ, আলোচনা সভা, ভক্ত সম্মেলন ও ধর্মীয় সাংস্কৃতিক অনুষ্ঠান।',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'slug' => 'other-religious',
                'name_en' => 'Other Religious Ceremony',
                'name_bn' => 'অন্যান্য ধর্মীয় অনুষ্ঠান',
                'description_en' => 'Special havans, yagyas, Gita path, Bhagwat saptaha, and specific community vows.',
                'description_bn' => 'হোম-যজ্ঞ, গীতা পাঠ, ভাগবত সপ্তাহ এবং বিশেষ ব্রত ও ধর্মীয় অনুষ্ঠান।',
                'status' => 'active',
                'is_system' => true,
            ],
        ];

        foreach ($categories as $cat) {
            EventCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
