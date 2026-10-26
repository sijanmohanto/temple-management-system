<?php

namespace App\Services;

use App\Models\CollectionPayment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptService
{
    /**
     * Issue an official receipt for a verified payment.
     *
     * @throws ValidationException
     */
    public function issueReceipt(CollectionPayment $payment, int $issuerId): Receipt
    {
        if ($payment->status !== CollectionPayment::STATUS_VERIFIED) {
            throw ValidationException::withMessages([
                'receipt' => __('donations.cannot_issue_receipt_for_unverified_payment'),
            ]);
        }

        if ($payment->receipt()->exists()) {
            return $payment->receipt;
        }

        $year = (int) ($payment->payment_year ?? date('Y'));

        return DB::transaction(function () use ($payment, $year, $issuerId) {
            $number = Receipt::generateReceiptNumber($year);

            $receipt = Receipt::create([
                'receipt_number' => $number,
                'collection_payment_id' => $payment->id,
                'receipt_year' => $year,
                'issued_at' => now(),
                'issued_by' => $issuerId,
                'status' => Receipt::STATUS_VALID,
            ]);

            AuditLogger::log(
                'receipt_issued',
                "Issued official receipt {$receipt->receipt_number} for payment {$payment->payment_code}",
                $receipt,
                $receipt->toArray()
            );

            return $receipt;
        });
    }

    /**
     * Void an issued receipt upon payment reversal.
     */
    public function voidReceipt(Receipt $receipt, string $reason, int $voiderId): bool
    {
        if ($receipt->isVoided()) {
            return true;
        }

        return DB::transaction(function () use ($receipt, $reason, $voiderId) {
            $receipt->update([
                'status' => Receipt::STATUS_VOIDED,
                'voided_at' => now(),
                'voided_by' => $voiderId,
                'void_reason' => $reason,
            ]);

            AuditLogger::log(
                'receipt_voided',
                "Voided receipt {$receipt->receipt_number}. Reason: {$reason}",
                $receipt,
                ['reason' => $reason]
            );

            return true;
        });
    }

    /**
     * Convert amount to words for professional receipt printing.
     */
    public function amountToWords(float $amount, string $locale = 'en'): string
    {
        $amount = round($amount, 2);
        $taka = (int) floor($amount);
        $poisha = (int) round(($amount - $taka) * 100);

        if ($locale === 'bn') {
            $takaText = $this->numberToBengaliWords($taka).' টাকা';
            if ($poisha > 0) {
                $takaText .= ' '.$this->numberToBengaliWords($poisha).' পয়সা';
            }

            return $takaText.' মাত্র';
        }

        $takaText = $this->numberToEnglishWords($taka).' Taka';
        if ($poisha > 0) {
            $takaText .= ' and '.$this->numberToEnglishWords($poisha).' Poisha';
        }

        return $takaText.' Only';
    }

    protected function numberToEnglishWords(int $num): string
    {
        if ($num === 0) {
            return 'Zero';
        }

        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
            20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
            60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety',
        ];

        if ($num < 20) {
            return $words[$num];
        }

        if ($num < 100) {
            return $words[10 * (int) ($num / 10)].($num % 10 ? ' '.$words[$num % 10] : '');
        }

        if ($num < 1000) {
            return $this->numberToEnglishWords((int) ($num / 100)).' Hundred'.($num % 100 ? ' and '.$this->numberToEnglishWords($num % 100) : '');
        }

        if ($num < 100000) {
            return $this->numberToEnglishWords((int) ($num / 1000)).' Thousand'.($num % 1000 ? ' '.$this->numberToEnglishWords($num % 1000) : '');
        }

        if ($num < 10000000) {
            return $this->numberToEnglishWords((int) ($num / 100000)).' Lakh'.($num % 100000 ? ' '.$this->numberToEnglishWords($num % 100000) : '');
        }

        return $this->numberToEnglishWords((int) ($num / 10000000)).' Crore'.($num % 10000000 ? ' '.$this->numberToEnglishWords($num % 10000000) : '');
    }

    protected function numberToBengaliWords(int $num): string
    {
        if ($num === 0) {
            return 'শূন্য';
        }

        $ones = [
            0 => '', 1 => 'এক', 2 => 'দুই', 3 => 'তিন', 4 => 'চার', 5 => 'পাঁচ',
            6 => 'ছয়', 7 => 'সাত', 8 => 'আট', 9 => 'নয়', 10 => 'দশ',
            11 => 'এগারো', 12 => 'বারো', 13 => 'তেরো', 14 => 'চৌদ্দ', 15 => 'পনেরো',
            16 => 'ষোলো', 17 => 'সতেরো', 18 => 'আঠারো', 19 => 'উনিশ', 20 => 'বিশ',
            21 => 'একুশ', 22 => 'বাইশ', 23 => 'তেইশ', 24 => 'চব্বিশ', 25 => 'পঁচিশ',
            26 => 'ছাব্বিশ', 27 => 'সাতাশ', 28 => 'আটাশ', 29 => 'উনত্রিশ', 30 => 'ত্রিশ',
            35 => 'পঁয়ত্রিশ', 40 => 'চল্লিশ', 45 => 'পঁয়তাল্লিশ', 50 => 'পঞ্চাশ',
            55 => 'পঞ্চান্ন', 60 => 'ষাট', 65 => 'পঁয়ষট্টি', 70 => 'সত্তর', 75 => 'পঁচাত্তর',
            80 => 'আশি', 85 => 'পঁচাশি', 90 => 'নব্বই', 95 => 'পঁচানব্বই', 100 => 'একশত',
        ];

        if (isset($ones[$num])) {
            return $ones[$num];
        }

        if ($num < 100) {
            return (string) $num;
        }

        if ($num < 1000) {
            return $this->numberToBengaliWords((int) ($num / 100)).' শত'.($num % 100 ? ' '.$this->numberToBengaliWords($num % 100) : '');
        }

        if ($num < 100000) {
            return $this->numberToBengaliWords((int) ($num / 1000)).' হাজার'.($num % 1000 ? ' '.$this->numberToBengaliWords($num % 1000) : '');
        }

        if ($num < 10000000) {
            return $this->numberToBengaliWords((int) ($num / 100000)).' লক্ষ'.($num % 100000 ? ' '.$this->numberToBengaliWords($num % 100000) : '');
        }

        return $this->numberToBengaliWords((int) ($num / 10000000)).' কোটি'.($num % 10000000 ? ' '.$this->numberToBengaliWords($num % 10000000) : '');
    }
}
