<?php

namespace Tests\Unit;

use App\Filament\Resources\InvoiceResource;
use PHPUnit\Framework\TestCase;

class InvoiceResourceDuplicateProductOptionTest extends TestCase
{
    public function test_disables_option_when_another_line_already_has_that_product(): void
    {
        $this->assertTrue(InvoiceResource::shouldDisableDuplicateProductOption(
            10,
            null,
            [10, null],
        ));
    }

    public function test_does_not_disable_current_rows_selected_product_when_duplicate_ids_in_state(): void
    {
        $this->assertFalse(InvoiceResource::shouldDisableDuplicateProductOption(
            5,
            5,
            [5, 5],
        ));
    }

    public function test_allows_product_when_only_this_row_selected_it(): void
    {
        $this->assertFalse(InvoiceResource::shouldDisableDuplicateProductOption(
            3,
            3,
            [3],
        ));
    }

    public function test_disables_when_two_distinct_products_and_third_line_would_repeat_first(): void
    {
        $this->assertTrue(InvoiceResource::shouldDisableDuplicateProductOption(
            1,
            null,
            [1, 2, null],
        ));
    }

    public function test_does_not_disable_unused_product_when_other_lines_use_different_products(): void
    {
        $this->assertFalse(InvoiceResource::shouldDisableDuplicateProductOption(
            9,
            null,
            [1, 2, null],
        ));
    }

    public function test_treats_empty_repeater_state_as_no_conflicts(): void
    {
        $this->assertFalse(InvoiceResource::shouldDisableDuplicateProductOption(
            1,
            null,
            [],
        ));
    }
}
