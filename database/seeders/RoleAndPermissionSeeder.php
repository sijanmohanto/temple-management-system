<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // System
            [
                'slug' => 'manage_users',
                'module' => 'system',
                'name_en' => 'Manage Users',
                'name_bn' => 'ব্যবহারকারী ব্যবস্থাপনা',
                'description_en' => 'Create, edit, activate and deactivate system users',
                'description_bn' => 'সিস্টেম ব্যবহারকারী তৈরি, সম্পাদনা, সক্রিয় ও নিষ্ক্রিয় করা',
            ],
            [
                'slug' => 'manage_roles',
                'module' => 'system',
                'name_en' => 'Manage Roles & Permissions',
                'name_bn' => 'ভূমিকা ও অনুমতি ব্যবস্থাপনা',
                'description_en' => 'Configure security roles and assign permissions',
                'description_bn' => 'নিরাপত্তা ভূমিকা কনফিগার ও অনুমতি বরাদ্দ করা',
            ],
            [
                'slug' => 'view_audit_logs',
                'module' => 'system',
                'name_en' => 'View Audit Logs',
                'name_bn' => 'অডিট লগ দর্শন',
                'description_en' => 'Inspect comprehensive security and financial audit trails',
                'description_bn' => 'নিরাপত্তা ও আর্থিক অডিট লগ পর্যালোচনা করা',
            ],
            [
                'slug' => 'manage_settings',
                'module' => 'system',
                'name_en' => 'Manage System Settings',
                'name_bn' => 'সিস্টেম সেটিংস পরিচালনা',
                'description_en' => 'Update temple branding, rules, and global configurations',
                'description_bn' => 'মন্দির তথ্য, নিয়মাবলী এবং বৈশ্বিক সেটিংস পরিবর্তন করা',
            ],

            // People
            [
                'slug' => 'view_people',
                'module' => 'people',
                'name_en' => 'View People & Families',
                'name_bn' => 'সদস্য ও পরিবার দর্শন',
                'description_en' => 'Browse devotee directory, family heads, and member profiles',
                'description_bn' => 'ভক্ত তালিকা, পরিবারের প্রধান ও সদস্যদের বিবরণ দেখা',
            ],
            [
                'slug' => 'manage_people',
                'module' => 'people',
                'name_en' => 'Manage People & Families',
                'name_bn' => 'সদস্য ও পরিবার ব্যবস্থাপনা',
                'description_en' => 'Add and update devotee, donor, and family records',
                'description_bn' => 'ভক্ত, দাতা এবং পরিবার রেকর্ড যুক্ত ও হালনাগাদ করা',
            ],
            [
                'slug' => 'manage_committee',
                'module' => 'people',
                'name_en' => 'Manage Committee & Roles',
                'name_bn' => 'কমিটি ও পদবি ব্যবস্থাপনা',
                'description_en' => 'Manage executive committee terms, designations, and monthly contributions',
                'description_bn' => 'কার্যনির্বাহী কমিটির মেয়াদ, পদবি ও মাসিক চাঁদা পরিচালনা',
            ],

            // Events & Puja
            [
                'slug' => 'view_events',
                'module' => 'events',
                'name_en' => 'View Events & Puja',
                'name_bn' => 'পূজা ও উৎসব দর্শন',
                'description_en' => 'View upcoming and historical religious events and puja editions',
                'description_bn' => 'আসন্ন ও পূর্ববর্তী ধর্মীয় অনুষ্ঠান এবং পূজা উৎসব দেখা',
            ],
            [
                'slug' => 'manage_events',
                'module' => 'events',
                'name_en' => 'Manage Events & Puja',
                'name_bn' => 'পূজা ও উৎসব ব্যবস্থাপনা',
                'description_en' => 'Create and configure puja editions, schedules, budgets, and volunteer assignments',
                'description_bn' => 'পূজার আসর, সময়সূচি, বাজেট ও সেবক দায়িত্ব বণ্টন পরিচালনা',
            ],

            // Finance & Ledger
            [
                'slug' => 'view_financial_reports',
                'module' => 'finance',
                'name_en' => 'View Financial Reports',
                'name_bn' => 'আর্থিক প্রতিবেদন দর্শন',
                'description_en' => 'Access temple income, expenditure, fund balances, and audit reports',
                'description_bn' => 'মন্দিরের আয়, ব্যয়, তহবিল স্থিতি ও অডিট প্রতিবেদন দেখা',
            ],
            [
                'slug' => 'record_donations',
                'module' => 'finance',
                'name_en' => 'Record Donations & Pledges',
                'name_bn' => 'অনুদান ও মানত সংগ্রহ',
                'description_en' => 'Record cash, online, and bank pledges, collections, and print official receipts',
                'description_bn' => 'নগদ, অনলাইন ও ব্যাংক অনুদান গ্রহণ, মানত অন্তর্ভুক্তি ও রসিদ প্রদান',
            ],
            [
                'slug' => 'verify_transactions',
                'module' => 'finance',
                'name_en' => 'Verify & Post Ledger Transactions',
                'name_bn' => 'লেনদেন যাচাই ও খতিয়ানে অন্তর্ভুক্তি',
                'description_en' => 'Review collected vouchers and post immutable entries to the centralized ledger',
                'description_bn' => 'আদায়কৃত ভাউচার যাচাই করে কেন্দ্রীয় খতিয়ানে চূড়ান্ত অনুমোদন দেওয়া',
            ],
            [
                'slug' => 'manage_expenses',
                'module' => 'finance',
                'name_en' => 'Manage Expenses & Vouchers',
                'name_bn' => 'ব্যয় ও ভাউচার ব্যবস্থাপনা',
                'description_en' => 'Record temple maintenance, event procurement, priest honorarium, and operational costs',
                'description_bn' => 'মন্দির রক্ষণাবেক্ষণ, পূজার কেনাকাটা, পুরোহিত দক্ষিণা ও পরিচালন ব্যয় লিপিবদ্ধ করা',
            ],

            // Venues & Booking
            [
                'slug' => 'view_venues',
                'module' => 'venues',
                'name_en' => 'View Venues & Hall Availability',
                'name_bn' => 'হলরুম ও ভেন্যু প্রাপ্যতা দর্শন',
                'description_en' => 'Check hall rental calendar, venue amenities, and schedule slots',
                'description_bn' => 'কমিউনিটি হল বুকিং ক্যালেন্ডার, সুযোগ-সুবিধা ও খালি স্লট দেখা',
            ],
            [
                'slug' => 'manage_venue_bookings',
                'module' => 'venues',
                'name_en' => 'Manage Venue Bookings',
                'name_bn' => 'ভেন্যু বুকিং পরিচালনা',
                'description_en' => 'Approve rental applications, manage security deposits, cancellations, and refunds',
                'description_bn' => 'ভাড়া আবেদন অনুমোদন, জামানত, বাতিল ও রিফান্ড পরিচালনা করা',
            ],

            // Operations & Daily Seva
            [
                'slug' => 'view_daily_tasks',
                'module' => 'operations',
                'name_en' => 'View Daily Temple Operations',
                'name_bn' => 'দৈনন্দিন কার্যক্রম দর্শন',
                'description_en' => 'Inspect daily puja timings, bhog schedules, and volunteer shifts',
                'description_bn' => 'দৈনিক পূজার সময়সূচি, ভোগ সেবা এবং সেবকদের শিফট দেখা',
            ],
            [
                'slug' => 'manage_daily_ops',
                'module' => 'operations',
                'name_en' => 'Manage Daily Operations & Assets',
                'name_bn' => 'দৈনন্দিন কার্যক্রম ও সম্পদ পরিচালনা',
                'description_en' => 'Assign daily duties, manage puja inventory, and coordinate facility maintenance',
                'description_bn' => 'দৈনিক দায়িত্ব অর্পণ, পূজার দ্রব্যাদি ইনভেন্টরি ও স্থাপনা রক্ষণাবেক্ষণ',
            ],
        ];

        $permissionModels = [];
        foreach ($permissions as $perm) {
            $permissionModels[$perm['slug']] = Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                $perm
            );
        }

        $roles = [
            'super_admin' => [
                'slug' => 'super_admin',
                'name_en' => 'Super Administrator',
                'name_bn' => 'প্রধান প্রশাসক',
                'description_en' => 'Complete system governance, security configuration, user management, and full audit authority.',
                'description_bn' => 'সম্পূর্ণ সিস্টেম পরিচালনা, নিরাপত্তা কনফিগারেশন, ব্যবহারকারী ব্যবস্থাপনা ও পূর্ণ অডিট ক্ষমতা।',
                'is_system' => true,
                'permissions' => array_keys($permissionModels), // all
            ],
            'temple_admin' => [
                'slug' => 'temple_admin',
                'name_en' => 'Temple Administrator',
                'name_bn' => 'মন্দির প্রশাসক',
                'description_en' => 'Operational administration for temple events, daily seva, venues, and member services.',
                'description_bn' => 'মন্দির উৎসব, দৈনন্দিন সেবা, ভেন্যু ও সদস্য সেবার সার্বিক প্রশাসনিক দায়িত্ব।',
                'is_system' => true,
                'permissions' => [
                    'view_people', 'manage_people', 'manage_committee',
                    'view_events', 'manage_events',
                    'record_donations', 'view_financial_reports',
                    'view_venues', 'manage_venue_bookings',
                    'view_daily_tasks', 'manage_daily_ops',
                ],
            ],
            'treasurer' => [
                'slug' => 'treasurer',
                'name_en' => 'Treasurer / Accountant',
                'name_bn' => 'কোষাধ্যক্ষ / হিসাবরক্ষক',
                'description_en' => 'Financial custody, double-entry ledger oversight, verified vouchers, and financial reports.',
                'description_bn' => 'আর্থিক হেফাজত, কেন্দ্রীয় খতিয়ান তত্ত্বাবধান, ভাউচার যাচাই এবং আর্থিক প্রতিবেদন প্রস্তুতকরণ।',
                'is_system' => true,
                'permissions' => [
                    'view_people',
                    'view_events',
                    'view_financial_reports', 'record_donations', 'verify_transactions', 'manage_expenses',
                    'view_venues', 'manage_venue_bookings',
                    'view_audit_logs',
                ],
            ],
            'committee_member' => [
                'slug' => 'committee_member',
                'name_en' => 'Executive Committee Member',
                'name_bn' => 'কার্যনির্বাহী সদস্য',
                'description_en' => 'Participation in executive meetings, monthly contributions, event planning, and report reviews.',
                'description_bn' => 'কার্যনির্বাহী সভায় অংশগ্রহণ, মাসিক চাঁদা প্রদান, উৎসব পরিকল্পনা ও হিসাব বিবরণী পর্যালোচনা।',
                'is_system' => true,
                'permissions' => [
                    'view_people',
                    'view_events',
                    'view_financial_reports',
                    'view_venues',
                    'view_daily_tasks',
                ],
            ],
            'volunteer' => [
                'slug' => 'volunteer',
                'name_en' => 'Temple Volunteer / Sevak',
                'name_bn' => 'মন্দির সেবক / স্বেচ্ছাসেবক',
                'description_en' => 'Assigned temple seva, event task execution, devotee crowd assistance, and donation desk support.',
                'description_bn' => 'অর্পণকৃত সেবা দায়িত্ব পালন, অনুষ্ঠান পরিচালনা, ভক্ত শৃঙ্খলা ও অনুদান কাউন্টারে সহায়তা।',
                'is_system' => true,
                'permissions' => [
                    'view_events',
                    'record_donations',
                    'view_venues',
                    'view_daily_tasks',
                ],
            ],
            'devotee' => [
                'slug' => 'devotee',
                'name_en' => 'Devotee / Member',
                'name_bn' => 'ভক্ত / সাধারণ সদস্য',
                'description_en' => 'Access to devotee self-service portal, donation history receipts, and venue booking requests.',
                'description_bn' => 'ভক্ত সেলফ-সার্ভিস পোর্টাল, দানের রসিদ ডাউনলোড ও অনুষ্ঠান বুকিং আবেদন।',
                'is_system' => true,
                'permissions' => [
                    'view_events',
                    'view_venues',
                    'view_daily_tasks',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $rolePermissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            $permIds = [];
            foreach ($rolePermissions as $permSlug) {
                if (isset($permissionModels[$permSlug])) {
                    $permIds[] = $permissionModels[$permSlug]->id;
                }
            }

            $role->permissions()->sync($permIds);
        }
    }
}
