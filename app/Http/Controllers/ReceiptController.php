<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use NumberToWords\NumberToWords;

class ReceiptController extends Controller
{
    public function download(Receipt $receipt)
    {
        Gate::authorize('download', $receipt);

        $pdf = PDF::loadView('receipts.print', [
            'receipt' => $receipt,
            'items' => $receipt->items ?? [],
            'amountInWords' => $this->amountInWords((float)$receipt->total),
        ]);

        return $pdf->download("Receipt-{$receipt->receipt_number}.pdf");
    }

    private function amountInWords(float $amount): string
    {
        $integer = floor($amount);
        $decimal = round(fmod($amount, 1) * 100);

        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('en');

        $words = ucfirst($numberTransformer->toWords($integer));
        $words = preg_replace('/thousand\s+(?=\w)/i', 'thousand and ', $words);
        $amountInWords = $words . ' Ghana Cedis';

        if ($decimal > 0) {
            $amountInWords .= ' and ' . $numberTransformer->toWords($decimal) . ' Pesewas';
        }

        return strtoupper($amountInWords . ' Only');
    }
}
