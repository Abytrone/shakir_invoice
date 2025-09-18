<?php

namespace App\Filament\Resources\AuthPaymentResource\Pages;

use App\Filament\Resources\AuthPaymentResource;
use App\Mail\AuthorizationUrlSent;
use App\Mail\VerificationUrlSent;
use App\Services\PaystackService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class ListAuthPayments extends ListRecords
{
    protected static string $resource = AuthPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
                ->using(function (array $data, string $model): Model {

                    $authService = app(PaystackService::class);

                    $paymentData = $authService->getAuthorizationUrl(
                        $data['auth_email'], $data['auth_phone']);

                    \Log::info('paymentData', (array)$paymentData);


                    $data['reference'] = $paymentData['reference'];
                    $data['authorization_url'] = $paymentData['authorization_url'];
                    $data['access_code'] = $paymentData['access_code'];
                    $data['amount'] = 1; // 1 GHS

                    try {
                        Mail::to($data['auth_email'])
                            ->send(new VerificationUrlSent($data['auth_email'], $data['authorization_url']));
                    }catch (\Exception $e){
                        \Illuminate\Log\log('failed to send email: '.$e->getMessage());
                    }

                    return $model::create($data);
                }),
        ];
    }
}
