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
            [
                'slug' => 'manage_event_categories',
                'module' => 'events',
                'name_en' => 'Manage Event Categories',
                'name_bn' => 'পূজা ও উৎসবের ধরন পরিচালনা',
                'description_en' => 'Create and configure religious puja types and community event categories',
                'description_bn' => 'পূজার প্রকারভেদ ও অনুষ্ঠানের ক্যাটাগরি তৈরি ও পরিচালনা করা',
            ],
            [
                'slug' => 'close_events',
                'module' => 'events',
                'name_en' => 'Close Events',
                'name_bn' => 'অনুষ্ঠান সমাপ্তকরণ',
                'description_en' => 'Perform event closing workflow with Close & Lock or Authorized Adjustments mode',
                'description_bn' => 'নির্দিষ্ট মোড নির্ধারণপূর্বক অনুষ্ঠান সমাপ্ত ও লক করা',
            ],
            [
                'slug' => 'reopen_events',
                'module' => 'events',
                'name_en' => 'Reopen Closed Events',
                'name_bn' => 'সমাপ্ত অনুষ্ঠান পুনরায় উন্মুক্তকরণ',
                'description_en' => 'Privileged reopening of closed events with mandatory reason and audit record',
                'description_bn' => 'কারণ লিপিবদ্ধকরণসহ সমাপ্ত অনুষ্ঠান পুনরায় চালু করা',
            ],
            [
                'slug' => 'adjust_closed_events',
                'module' => 'events',
                'name_en' => 'Adjust Closed Events Metadata',
                'name_bn' => 'সমাপ্ত অনুষ্ঠানের তথ্য সংশোধন',
                'description_en' => 'Perform authorized post-closing metadata adjustments in Mode B with reason logging',
                'description_bn' => 'অনুমোদন সাপেক্ষে সমাপ্ত অনুষ্ঠানের তথ্য সংশোধন ও অডিট ট্রেইল রাখা',
            ],
            [
                'slug' => 'view_event_audit',
                'module' => 'events',
                'name_en' => 'View Event Lifecycle History',
                'name_bn' => 'অনুষ্ঠানের ইতিহাস ও অডিট ট্রেইল দর্শন',
                'description_en' => 'Inspect complete event lifecycle, closing and reopening audit history',
                'description_bn' => 'অনুষ্ঠানের স্থিতি পরিবর্তন, সমাপ্তি ও খোলার বিস্তারিত বিবরণ দর্শন',
            ],

            // Finance & Ledger
            [
                'slug' => 'view_finance_dashboard',
                'module' => 'finance',
                'name_en' => 'View Finance Dashboard',
                'name_bn' => 'আর্থিক ড্যাশবোর্ড দর্শন',
                'description_en' => 'Access real-time treasury balances, financial metrics, and unposted collections',
                'description_bn' => 'রিয়েল-টাইম তহবিল স্থিতি, আর্থিক মেট্রিক্স ও পোস্টিং অপেক্ষমাণ সংগ্রহ দেখা',
            ],
            [
                'slug' => 'view_accounts',
                'module' => 'finance',
                'name_en' => 'View Chart of Accounts',
                'name_bn' => 'হিসাব তালিকা দর্শন',
                'description_en' => 'View hierarchical chart of accounts and live account balances',
                'description_bn' => 'হিসাবের ক্রমবিন্যাস ও চলতি ব্যালেন্স দেখা',
            ],
            [
                'slug' => 'manage_accounts',
                'module' => 'finance',
                'name_en' => 'Manage Chart of Accounts',
                'name_bn' => 'হিসাব তালিকা পরিচালনা',
                'description_en' => 'Create and edit non-system ledger accounts and descriptions',
                'description_bn' => 'নন-সিস্টেম হিসাব তৈরি ও সম্পাদনা করা',
            ],
            [
                'slug' => 'view_journals',
                'module' => 'finance',
                'name_en' => 'View Journal Entries',
                'name_bn' => 'জার্নাল ও খতিয়ান ভাউচার দর্শন',
                'description_en' => 'Inspect double-entry journal vouchers, lines, and debit-credit balances',
                'description_bn' => 'দ্বৈত-দাখিলা জার্নাল ভাউচার, লাইন ও ডেবিট-ক্রেডিট ব্যালেন্স দেখা',
            ],
            [
                'slug' => 'view_income',
                'module' => 'finance',
                'name_en' => 'View Miscellaneous Income',
                'name_bn' => 'বিবিধ আয় দর্শন',
                'description_en' => 'Browse manual miscellaneous temple income entries and vouchers',
                'description_bn' => 'ম্যানুয়াল বিবিধ মন্দির আয়ের রেকর্ড ও ভাউচার দেখা',
            ],
            [
                'slug' => 'record_income',
                'module' => 'finance',
                'name_en' => 'Record Miscellaneous Income',
                'name_bn' => 'বিবিধ আয় লিপিবদ্ধকরণ',
                'description_en' => 'Record non-devotee miscellaneous temple income directly into the ledger',
                'description_bn' => 'অ-ভক্ত বিবিধ মন্দির আয় সরাসরি খতিয়ানে এন্ট্রি করা',
            ],
            [
                'slug' => 'view_expenses',
                'module' => 'finance',
                'name_en' => 'View Expenses',
                'name_bn' => 'ব্যয় বিবরণী দর্শন',
                'description_en' => 'Browse temple expense requisitions, vouchers, and approval states',
                'description_bn' => 'মন্দিরের খরচের রিকুইজিশন, ভাউচার ও অনুমোদন অবস্থা দেখা',
            ],
            [
                'slug' => 'record_expenses',
                'module' => 'finance',
                'name_en' => 'Record Temple Expenses',
                'name_bn' => 'মন্দিরের ব্যয় লিপিবদ্ধকরণ',
                'description_en' => 'Submit expense requisitions for puja, operational, and maintenance costs',
                'description_bn' => 'পূজা, পরিচালনা ও রক্ষণাবেক্ষণ খরচের বিল বা রিকুইজিশন দাখিল করা',
            ],
            [
                'slug' => 'approve_expenses',
                'module' => 'finance',
                'name_en' => 'Approve & Reject Expenses',
                'name_bn' => 'ব্যয় অনুমোদন ও প্রত্যাখ্যান',
                'description_en' => 'Approve or reject pending expense vouchers with authorization logging',
                'description_bn' => 'অপেক্ষমাণ খরচের ভাউচার অনুমোদন বা প্রত্যাখ্যান করা',
            ],
            [
                'slug' => 'post_transactions',
                'module' => 'finance',
                'name_en' => 'Post Finance Ledger Transactions',
                'name_bn' => 'খতিয়ানে চূড়ান্ত পোস্টিং',
                'description_en' => 'Post approved vouchers, collections, and manual entries to the permanent ledger',
                'description_bn' => 'অনুমোদিত ভাউচার ও আদায়সমূহ স্থায়ী খতিয়ানে পোস্ট করা',
            ],
            [
                'slug' => 'view_transfers',
                'module' => 'finance',
                'name_en' => 'View Fund Transfers',
                'name_bn' => 'তহবিল স্থানান্তর দর্শন',
                'description_en' => 'Inspect inter-account fund transfers between cash, bank, and MFS',
                'description_bn' => 'নগদ, ব্যাংক ও এমএফএস অ্যাকাউন্টের মধ্যকার স্থানান্তর বিবরণী দেখা',
            ],
            [
                'slug' => 'manage_transfers',
                'module' => 'finance',
                'name_en' => 'Manage Fund Transfers',
                'name_bn' => 'তহবিল স্থানান্তর সম্পাদন',
                'description_en' => 'Execute fund transfers between temple cash, bank, and mobile financial accounts',
                'description_bn' => 'মন্দিরের নগদ, ব্যাংক ও মোবাইল হিসাবের মধ্যে অর্থ স্থানান্তর করা',
            ],
            [
                'slug' => 'manage_opening_balances',
                'module' => 'finance',
                'name_en' => 'Manage Opening Balances',
                'name_bn' => 'প্রারম্ভিক জের পরিচালনা',
                'description_en' => 'Set and adjust initial account balances balancing against Opening Balance Equity',
                'description_bn' => 'প্রারম্ভিক ব্যালেন্স এন্ট্রি ও ইকুইটির বিপরীতে সমন্বয় করা',
            ],
            [
                'slug' => 'close_accounting_period',
                'module' => 'finance',
                'name_en' => 'Close Accounting Periods',
                'name_bn' => 'হিসাবকাল বন্ধকরণ',
                'description_en' => 'Lock completed fiscal months or years against retrospective journal entries',
                'description_bn' => 'পূর্ববর্তী অর্থবছর বা মাস বন্ধ ও লক করা',
            ],
            [
                'slug' => 'reverse_transactions',
                'module' => 'finance',
                'name_en' => 'Reverse Posted Transactions',
                'name_bn' => 'পোস্টকৃত লেনদেন প্রত্যাহার',
                'description_en' => 'Create balanced inverse journal entries to safely correct posted mistakes',
                'description_bn' => 'কারণ দর্শিয়ে বিপরীত দাখিলার মাধ্যমে পোস্টকৃত লেনদেন সংশোধন করা',
            ],
            [
                'slug' => 'view_financial_reports',
                'module' => 'finance',
                'name_en' => 'View Financial Reports',
                'name_bn' => 'আর্থিক প্রতিবেদন দর্শন',
                'description_en' => 'Access temple income, expenditure, trial balance, ledger, and cash book',
                'description_bn' => 'মন্দিরের আয়-ব্যয়, রেওয়ামিল, খতিয়ান ও ক্যাশ বই প্রতিবেদন দেখা',
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

            // Phase 4: Donations, Pledges & Collections
            [
                'slug' => 'view_pledges',
                'module' => 'collections',
                'name_en' => 'View Pledges & Commitments',
                'name_bn' => 'মানত ও অঙ্গীকার দর্শন',
                'description_en' => 'View event-specific devotee and family pledges and collection progress',
                'description_bn' => 'উৎসবভিত্তিক ভক্ত ও পরিবারের মানত ও আদায় অগ্রগতি দর্শন',
            ],
            [
                'slug' => 'manage_pledges',
                'module' => 'collections',
                'name_en' => 'Manage Event Pledges',
                'name_bn' => 'মানত ও অঙ্গীকার ব্যবস্থাপনা',
                'description_en' => 'Create, edit, and cancel event pledges for individuals and families',
                'description_bn' => 'ব্যক্তি ও পরিবারের জন্য উৎসবভিত্তিক মানত তৈরি, সংশোধন ও বাতিল করা',
            ],
            [
                'slug' => 'view_collections',
                'module' => 'collections',
                'name_en' => 'View Collections & Payments',
                'name_bn' => 'আদায় ও পেমেন্ট দর্শন',
                'description_en' => 'Inspect collection dashboards, pending verification queues, and payment logs',
                'description_bn' => 'আদায় ড্যাশবোর্ড, যাচাইয়ের অপেক্ষমাণ তালিকা ও পেমেন্ট রেকর্ড দেখা',
            ],
            [
                'slug' => 'record_collections',
                'module' => 'collections',
                'name_en' => 'Record & Submit Payments',
                'name_bn' => 'পেমেন্ট ও চাঁদা গ্রহণ',
                'description_en' => 'Submit collection payments for pledges, voluntary donations, and monthly dues',
                'description_bn' => 'মানত, ঐচ্ছিক অনুদান ও মাসিক চাঁদার বিপরীতে পেমেন্ট লিপিবদ্ধ করা',
            ],
            [
                'slug' => 'verify_collections',
                'module' => 'collections',
                'name_en' => 'Verify & Reject Payment Submissions',
                'name_bn' => 'পেমেন্ট যাচাই ও অনুমোদন',
                'description_en' => 'Authorized verification or rejection of payment submissions with receipt generation',
                'description_bn' => 'পেমেন্ট যাচাইপূর্বক রসিদ প্রদান বা উপযুক্ত কারণ দর্শিয়ে বাতিল করা',
            ],
            [
                'slug' => 'reverse_collections',
                'module' => 'collections',
                'name_en' => 'Reverse Verified Collections',
                'name_bn' => 'যাচাইকৃত পেমেন্ট প্রত্যাহার ও সংশোধন',
                'description_en' => 'Authorize reversing adjustments for verified payments with mandatory audit reason',
                'description_bn' => 'যাচাইকৃত পেমেন্ট বাতিল ও রসিদ বাতিলকরণসহ কারণ লিপিবদ্ধ করা',
            ],
            [
                'slug' => 'view_sensitive_payment_details',
                'module' => 'collections',
                'name_en' => 'View Sensitive Payment References',
                'name_bn' => 'গোপনীয় পেমেন্ট তথ্য দর্শন',
                'description_en' => 'View protected bank account details, cheque numbers, and transaction IDs',
                'description_bn' => 'সুরক্ষিত ব্যাংক অ্যাকাউন্ট, চেক নম্বর ও ট্রানজ্যাকশন আইডি দেখা',
            ],
            [
                'slug' => 'view_receipts',
                'module' => 'collections',
                'name_en' => 'View Official Money Receipts',
                'name_bn' => 'অর্থপ্রাপ্তির রসিদ দর্শন',
                'description_en' => 'Access verified payment receipt register and receipt details',
                'description_bn' => 'যাচাইকৃত পেমেন্টের অফিশিয়াল রসিদ ও বিস্তারিত তথ্য দেখা',
            ],
            [
                'slug' => 'print_receipts',
                'module' => 'collections',
                'name_en' => 'Print Money Receipts',
                'name_bn' => 'রসিদ মুদ্রণ',
                'description_en' => 'Print and reprint official temple money receipts',
                'description_bn' => 'মন্দিরের অনুমোদিত অর্থপ্রাপ্তি রসিদ প্রিন্ট করা',
            ],
            [
                'slug' => 'view_committee_contributions',
                'module' => 'collections',
                'name_en' => 'View Monthly Committee Contributions',
                'name_bn' => 'কমিটির মাসিক চাঁদা দর্শন',
                'description_en' => 'View monthly committee member contribution obligations, paid status, and dues',
                'description_bn' => 'কমিটি সদস্যদের মাসিক চাঁদা নির্ধারণ, আদায়ের স্থিতি ও বকেয়া দেখা',
            ],
            [
                'slug' => 'manage_committee_contributions',
                'module' => 'collections',
                'name_en' => 'Manage Committee Contributions',
                'name_bn' => 'কমিটির মাসিক চাঁদা পরিচালনা',
                'description_en' => 'Generate and configure monthly committee contribution obligations',
                'description_bn' => 'কমিটি সদস্যদের জন্য মাসিক চাঁদার বাধ্যবাধকতা তৈরি ও সমন্বয় করা',
            ],
            [
                'slug' => 'view_collection_reports',
                'module' => 'collections',
                'name_en' => 'View Collection Reports',
                'name_bn' => 'আদায় সংক্রান্ত প্রতিবেদন দর্শন',
                'description_en' => 'Access event-wise collection summaries, pledge balances, and receipt registers',
                'description_bn' => 'উৎসবভিত্তিক আদায় বিবরণী, বকেয়া মানত ও রসিদ রেজিস্টার দেখা',
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
                    'view_events', 'manage_events', 'manage_event_categories', 'close_events', 'reopen_events', 'adjust_closed_events', 'view_event_audit',
                    'record_donations', 'view_financial_reports',
                    'view_finance_dashboard', 'view_accounts', 'view_journals', 'view_income', 'record_income', 'view_expenses', 'record_expenses', 'view_transfers', 'manage_transfers',
                    'view_pledges', 'manage_pledges', 'view_collections', 'record_collections', 'verify_collections',
                    'view_sensitive_payment_details', 'view_receipts', 'print_receipts',
                    'view_committee_contributions', 'manage_committee_contributions', 'view_collection_reports',
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
                    'view_events', 'view_event_audit',
                    'view_finance_dashboard', 'view_accounts', 'manage_accounts', 'view_journals',
                    'view_income', 'record_income', 'view_expenses', 'record_expenses', 'approve_expenses',
                    'post_transactions', 'view_transfers', 'manage_transfers', 'manage_opening_balances',
                    'close_accounting_period', 'reverse_transactions',
                    'view_financial_reports', 'record_donations', 'verify_transactions', 'manage_expenses',
                    'view_pledges', 'manage_pledges', 'view_collections', 'record_collections', 'verify_collections', 'reverse_collections',
                    'view_sensitive_payment_details', 'view_receipts', 'print_receipts',
                    'view_committee_contributions', 'manage_committee_contributions', 'view_collection_reports',
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
                    'view_events', 'view_event_audit',
                    'view_finance_dashboard', 'view_accounts', 'view_journals', 'view_financial_reports',
                    'view_pledges', 'view_collections', 'view_receipts',
                    'view_committee_contributions', 'view_collection_reports',
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
                    'view_pledges', 'view_collections', 'record_collections', 'view_receipts',
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
