<?php

namespace Database\Seeders;

use App\Models\FinancialAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // Assets (1000s)
            [
                'account_code' => '1010',
                'account_name_en' => 'Cash in Hand',
                'account_name_bn' => 'কাউন্টারে নগদ অর্থ',
                'account_type' => FinancialAccount::TYPE_ASSET,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Physical cash received and stored in temple counter vaults',
            ],
            [
                'account_code' => '1020',
                'account_name_en' => 'Main Temple Bank Account',
                'account_name_bn' => 'প্রধান মন্দির ব্যাংক হিসাব',
                'account_type' => FinancialAccount::TYPE_ASSET,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Primary temple bank operating account for deposits, transfers, and cheques',
            ],
            [
                'account_code' => '1030',
                'account_name_en' => 'bKash / Nagad / Rocket MFS',
                'account_name_bn' => 'মোবাইল ফিন্যান্সিয়াল সার্ভিস (বিকাশ/নগদ/রকেট)',
                'account_type' => FinancialAccount::TYPE_ASSET,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Authorized merchant/personal mobile financial service accounts',
            ],

            // Liabilities (2000s)
            [
                'account_code' => '2010',
                'account_name_en' => 'Accounts Payable',
                'account_name_bn' => 'প্রদেয় হিসাব',
                'account_type' => FinancialAccount::TYPE_LIABILITY,
                'normal_balance' => FinancialAccount::BALANCE_CREDIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Outstanding obligations to suppliers, decorators, and contractors',
            ],

            // Equity / Fund Balance (3000s)
            [
                'account_code' => '3010',
                'account_name_en' => 'General Temple Fund',
                'account_name_bn' => 'সাধারণ মন্দির তহবিল',
                'account_type' => FinancialAccount::TYPE_EQUITY,
                'normal_balance' => FinancialAccount::BALANCE_CREDIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Accumulated fund balance and general reserve of the temple',
            ],
            [
                'account_code' => '3020',
                'account_name_en' => 'Opening Balance Equity',
                'account_name_bn' => 'প্রারম্ভিক উদ্বৃত্ত হিসাব',
                'account_type' => FinancialAccount::TYPE_EQUITY,
                'normal_balance' => FinancialAccount::BALANCE_CREDIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Balancing equity account for initial balance setup and period opening entries',
            ],

            // Income (4000s)
            [
                'account_code' => '4010',
                'account_name_en' => 'General Donation Income',
                'account_name_bn' => 'সাধারণ প্রণামী ও অনুদান আয়',
                'account_type' => FinancialAccount::TYPE_INCOME,
                'normal_balance' => FinancialAccount::BALANCE_CREDIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Voluntary walk-in donations and unrestricted general temple offerings',
            ],
            [
                'account_code' => '4020',
                'account_name_en' => 'Event & Puja Donation Income',
                'account_name_bn' => 'পূজা ও উৎসব অনুদান আয়',
                'account_type' => FinancialAccount::TYPE_INCOME,
                'normal_balance' => FinancialAccount::BALANCE_CREDIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Donations, pledges, and sponsorships specific to religious festivals and puja editions',
            ],
            [
                'account_code' => '4030',
                'account_name_en' => 'Monthly Committee Contribution Income',
                'account_name_bn' => 'কমিটির মাসিক চাঁদা আয়',
                'account_type' => FinancialAccount::TYPE_INCOME,
                'normal_balance' => FinancialAccount::BALANCE_CREDIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Non-refundable monthly contributions collected from executive committee members',
            ],
            [
                'account_code' => '4040',
                'account_name_en' => 'Miscellaneous Temple Income',
                'account_name_bn' => 'বিবিধ মন্দির আয়',
                'account_type' => FinancialAccount::TYPE_INCOME,
                'normal_balance' => FinancialAccount::BALANCE_CREDIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Bank profit/interest, hall bookings, tender forms, and miscellaneous receipts',
            ],

            // Expenses (5000s)
            [
                'account_code' => '5010',
                'account_name_en' => 'Puja & Religious Celebration Expenses',
                'account_name_bn' => 'পূজা ও ধর্মীয় অনুষ্ঠান ব্যয়',
                'account_type' => FinancialAccount::TYPE_EXPENSE,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Ritual materials, pratima crafting, flowers, ghee, and puja procurement',
            ],
            [
                'account_code' => '5020',
                'account_name_en' => 'Temple Maintenance & Repairs',
                'account_name_bn' => 'মন্দির রক্ষণাবেক্ষণ ও সংস্কার',
                'account_type' => FinancialAccount::TYPE_EXPENSE,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Building repairs, painting, plumbing, civil maintenance, and security',
            ],
            [
                'account_code' => '5030',
                'account_name_en' => 'Utilities (Electricity, Water, Gas)',
                'account_name_bn' => 'বিদ্যুৎ, পানি ও গ্যাস বিল',
                'account_type' => FinancialAccount::TYPE_EXPENSE,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'DESCO/NESCO electricity bills, WASA water bills, and municipal taxes',
            ],
            [
                'account_code' => '5040',
                'account_name_en' => 'Decoration, Lighting & Sound',
                'account_name_bn' => 'আলোকসজ্জা, ডেকোরেশন ও সাউন্ড সিস্টেম',
                'account_type' => FinancialAccount::TYPE_EXPENSE,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Pandals, illumination, banners, audio systems, and stage setup',
            ],
            [
                'account_code' => '5050',
                'account_name_en' => 'Bhog & Prasad Distribution',
                'account_name_bn' => 'ভোগ ও প্রসাদ বিতরণ',
                'account_type' => FinancialAccount::TYPE_EXPENSE,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Rice, lentils, fruits, sweets, cooking, and mass community feeding',
            ],
            [
                'account_code' => '5060',
                'account_name_en' => 'Priest Honorarium & Seva',
                'account_name_bn' => 'পুরোহিত দক্ষিণা ও সেবা সম্মানী',
                'account_type' => FinancialAccount::TYPE_EXPENSE,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Dakshina for head priests, tantrodharaks, drummers (dhaki), and assistants',
            ],
            [
                'account_code' => '5070',
                'account_name_en' => 'Bank & Financial Service Charges',
                'account_name_bn' => 'ব্যাংক ও আর্থিক সার্ভিস চার্জ',
                'account_type' => FinancialAccount::TYPE_EXPENSE,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Bank maintenance fees, cheque book charges, MFS cashout fees, and excise duty',
            ],
            [
                'account_code' => '5080',
                'account_name_en' => 'Other Miscellaneous Expenses',
                'account_name_bn' => 'অন্যান্য বিবিধ খরচ',
                'account_type' => FinancialAccount::TYPE_EXPENSE,
                'normal_balance' => FinancialAccount::BALANCE_DEBIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Printing, stationery, communication, hospitality, and unclassified petty expenses',
            ],
        ];

        foreach ($accounts as $account) {
            FinancialAccount::updateOrCreate(
                ['account_code' => $account['account_code']],
                $account
            );
        }
    }
}
