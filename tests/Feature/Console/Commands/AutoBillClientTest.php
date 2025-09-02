<?php

namespace Tests\Feature\Console\Commands;


use App\Console\Commands\AutoBillClient;
use App\Constants\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoBillClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }


    public function test_it_can_save_auth_data(): void
    {
        $client = Client::factory()->create([
            'auth_email' => 'auth@localhost.com',
        ]);


        \Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response($this->getFakeVerifyResponse(withAuthEmail: true)),
        ]);

        $this->getJson(
            route('payments.process', ['reference' => 're4lyvq3s3'])
        );

        $this->assertNotNull($client->fresh()->auth_res);
    }

    public function test_it_bills_clients_with_authorization(): void
    {
        $client = Client::factory()->create([
            'auth_email' => 'auth@localhost.com',
            'auth_res' => json_encode($this->getFakeVerifyResponse()['data']['authorization'])
        ]);

        $invoice = Invoice::factory()->create([
            'tax_rate' => 0,
            'discount_rate' => 0,
            'client_id' => $client->id,
            'status' => InvoiceStatus::UNPAID,
            'is_recurring' => true,
            'due_date' => now()->subDays(),
        ]);

        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
            'unit_price' => 10
        ]);

         Invoice::factory()->create([
            'tax_rate' => 0,
            'discount_rate' => 0,
            'client_id' => $client->id,
            'status' => InvoiceStatus::UNPAID,
            'is_recurring' => false,
            'due_date' => now()->subDays(),
        ]);

        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
            'unit_price' => 10
        ]);

        $this->artisan(AutoBillClient::class)
            ->expectsOutput('Starting auto bill client...')
            ->expectsOutput('1 has been billed...')
            ->assertExitCode(0);

        \Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response($this->getFakeVerifyResponse($invoice->invoice_number)),
            'https://api.paystack.co/transaction/charge_authorization' => Http::response($this->fakeChargeResponse),
        ]);

        $this->getJson(route('payments.process', ['reference' => 're4lyvq3s3']));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'auth_email' => 'auth@localhost.com'
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => InvoiceStatus::PAID,
        ]);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id
        ]);
    }


    public $fakeChargeResponse = [
        "status" => true,
        "message" => "Charge attempted",
        "data" => [
            "amount" => 35247,
            "currency" => "NGN",
            "transaction_date" => "2024-08-22T10:53:49.000Z",
            "status" => "success",
            "reference" => "0m7frfnr47ezyxl",
            "domain" => "test",
            "metadata" => "",
            "gateway_response" => "Approved",
            "message" => null,
            "channel" => "card",
            "ip_address" => null,
            "log" => null,
            "fees" => 10247,
            "authorization" => [
                "authorization_code" => "AUTH_uh8bcl3zbn",
                "bin" => "408408",
                "last4" => "4081",
                "exp_month" => "12",
                "exp_year" => "2030",
                "channel" => "card",
                "card_type" => "visa ",
                "bank" => "TEST BANK",
                "country_code" => "NG",
                "brand" => "visa",
                "reusable" => true,
                "signature" => "SIG_yEXu7dLBeqG0kU7g95Ke",
                "account_name" => null
            ],
            "customer" => [
                "id" => 181873746,
                "first_name" => null,
                "last_name" => null,
                "email" => "demo@test.com",
                "customer_code" => "CUS_1rkzaqsv4rrhqo6",
                "phone" => null,
                "metadata" => [
                    "custom_fields" => [
                        [
                            "display_name" => "Customer email",
                            "variable_name" => "customer_email",
                            "value" => "new@email.com"
                        ]
                    ]
                ],
                "risk_action" => "default",
                "international_format_phone" => null
            ],
            "plan" => null,
            "id" => 4099490251
        ]
    ];

    public function getFakeVerifyResponse(?string $invoiceNumber = null, bool $withAuthEmail = false): array
    {
        $customFields = [
            [

            ],
            [
                "value" => $invoiceNumber,
                "display_name" => "Invoice Number",
                "variable_name" => "invoice_number"
            ],
        ];

        if($withAuthEmail){
            $customFields[] = [
                'display_name' => 'Auth Email',
                'variable_name' => 'auth_email',
                'value' => "auth@localhost.com",
            ];
        }
        return [
            "status" => true,
            "message" => "Verification successful",
            "data" => [
                "id" => 4099260516,
                "domain" => "test",
                "status" => "success",
                "reference" => "re4lyvq3s3",
                "receipt_number" => null,
                "amount" => 40333,
                "message" => null,
                "gateway_response" => "Successful",
                "paid_at" => "2024-08-22T09:15:02.000Z",
                "created_at" => "2024-08-22T09:14:24.000Z",
                "channel" => "card",
                "currency" => "NGN",
                "ip_address" => "197.210.54.33",
                "metadata" => [
                    "custom_fields" => $customFields
                ],
                "log" => [
                    "start_time" => 1724318098,
                    "time_spent" => 4,
                    "attempts" => 1,
                    "errors" => 0,
                    "success" => true,
                    "mobile" => false,
                    "input" => [],
                    "history" => [
                        [
                            "type" => "action",
                            "message" => "Attempted to pay with card",
                            "time" => 3
                        ],
                        [
                            "type" => "success",
                            "message" => "Successfully paid with card",
                            "time" => 4
                        ]
                    ]
                ],
                "fees" => 10283,
                "fees_split" => null,
                "authorization" => [
                    "authorization_code" => "AUTH_uh8bcl3zbn",
                    "bin" => "408408",
                    "last4" => "4081",
                    "exp_month" => "12",
                    "exp_year" => "2030",
                    "channel" => "card",
                    "card_type" => "visa ",
                    "bank" => "TEST BANK",
                    "country_code" => "NG",
                    "brand" => "visa",
                    "reusable" => true,
                    "signature" => "SIG_yEXu7dLBeqG0kU7g95Ke",
                    "account_name" => null
                ],
                "customer" => [
                    "id" => 181873746,
                    "first_name" => null,
                    "last_name" => null,
                    "email" => "demo@test.com",
                    "customer_code" => "CUS_1rkzaqsv4rrhqo6",
                    "phone" => null,
                    "metadata" => null,
                    "risk_action" => "default",
                    "international_format_phone" => null
                ],
                "plan" => null,
                "split" => [],
                "order_id" => null,
                "paidAt" => "2024-08-22T09:15:02.000Z",
                "createdAt" => "2024-08-22T09:14:24.000Z",
                "requested_amount" => 30050,
                "pos_transaction_data" => null,
                "source" => null,
                "fees_breakdown" => null,
                "connect" => null,
                "transaction_date" => "2024-08-22T09:14:24.000Z",
                "plan_object" => [],
                "subaccount" => []
            ]
        ];
    }


}
