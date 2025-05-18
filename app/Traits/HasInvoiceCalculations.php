<?php

namespace App\Traits;

trait HasInvoiceCalculations
{
    public function calculateTotals(): void
    {
        $items = collect($this->data['items'] ?? []);

        // Calculate item totals with their individual discounts
        $subtotal = 0;
        $taxAmount = 0;
        $itemDiscountAmount = 0;

        foreach ($items as $item) {
            $itemSubtotal = round(floatval($item['quantity'] ?? 0) * floatval($item['unit_price'] ?? 0), 2);
            $itemDiscountRate = floatval($item['discount_rate'] ?? 0);
            $itemDiscount = round($itemSubtotal * ($itemDiscountRate / 100), 2);
            $itemSubtotalAfterDiscount = round($itemSubtotal - $itemDiscount, 2);
            $itemTaxAmount = round($itemSubtotalAfterDiscount * (floatval($item['tax_rate'] ?? 0) / 100), 2);

            $subtotal += $itemSubtotal;
            $itemDiscountAmount += $itemDiscount;
            $taxAmount += $itemTaxAmount;
        }

        // Round accumulated totals
        $subtotal = round($subtotal, 2);
        $itemDiscountAmount = round($itemDiscountAmount, 2);
        $taxAmount = round($taxAmount, 2);

        // Apply invoice-level discount on the subtotal after item discounts
        $invoiceDiscountRate = floatval($this->data['discount_rate'] ?? 0);
        $invoiceDiscountAmount = round($subtotal * ($invoiceDiscountRate / 100), 2);
        $subtotalAfterDiscount = round($subtotal - $invoiceDiscountAmount, 2);

        // Calculate final totals
        $grandTotal = round($subtotalAfterDiscount + $taxAmount, 2);
        $balance = round($grandTotal - floatval($this->data['amount_paid'] ?? 0), 2);

        // Update form data
        $this->data['subtotal'] = $subtotal;
        $this->data['tax_amount'] = $taxAmount;
        $this->data['discount_amount'] = round($itemDiscountAmount + $invoiceDiscountAmount, 2);
        $this->data['grand_total'] = $grandTotal;
        $this->data['balance'] = $balance;
    }

    public function validateAmounts(): void
    {
        $items = collect($this->data['items'] ?? []);

        // Validate no negative amounts
        if ($this->data['subtotal'] < 0 || $this->data['grand_total'] < 0 || $this->data['balance'] < 0) {
            throw new \Exception('Amounts cannot be negative');
        }

        // Validate amount paid doesn't exceed total
        if (floatval($this->data['amount_paid'] ?? 0) > floatval($this->data['grand_total'] ?? 0)) {
            throw new \Exception('Amount paid cannot exceed total amount');
        }
    }
}