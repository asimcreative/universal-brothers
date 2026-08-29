<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * FAQs derived directly from the Hajj 2027 brochure's own Payment Plan,
 * Required Documents, and Terms & Conditions pages — answers are sourced
 * facts, not invented copy.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'category' => 'hajj',
                'question' => 'What is the Hajj 2027 payment plan?',
                'answer' => '50% is due at the time of booking, 25% by 15 September 2026, and the remaining 25% by 15 December 2026.',
            ],
            [
                'category' => 'hajj',
                'question' => 'What documents are required for the Hajj application?',
                'answer' => 'A passport copy (valid to 30 November 2027), two 4x3cm photographs on a white background, a NIC/ID card copy (NADRA), next-of-kin ID card, contact number and relation, the passenger\'s blood group, and a children\'s B-Form (NADRA) where applicable.',
            ],
            [
                'category' => 'hajj',
                'question' => 'Is the airline ticket included in the Hajj package price?',
                'answer' => 'No. Package prices exclude the airline ticket, which is approximately PKR 335,000 from Karachi and PKR 345,000 from North Pakistan (fares vary for Hajis travelling from international destinations). Qurbani is also excluded — assistance in arranging it is included, at an approximate charge of US$200.',
            ],
            [
                'category' => 'hajj',
                'question' => 'What happens if I need to cancel my Hajj booking?',
                'answer' => 'If cancelled due to an emergency before 15 December 2026, a full refund is issued after deducting US$350 per person in service charges. After 15 December 2026 and before Hajj visa issuance, the booking is treated as a substitution — you may nominate another person or the operator may offer the package to a waitlisted applicant — with the same US$350 per person service charge deducted.',
            ],
            [
                'category' => 'general',
                'question' => 'How can I contact Universal Brothers?',
                'answer' => 'Landline: (92-21) 111-102-786 or (92-21) 111-106-786. WhatsApp: +92 322 2102786. Email: info@maximsgroup.org. Office: A-9, 1st Floor, Hassan Homes, FL-3/8, Opposite Nehr-e-Khayyam, KDA Scheme Block-5, Clifton, Karachi.',
            ],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['sort_order' => $i, 'is_active' => true])
            );
        }
    }
}
