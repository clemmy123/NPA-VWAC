<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JumuishiClient
{
    private function client(): PendingRequest
    {
        if (! config('jumuishi.enabled') || ! filled(config('jumuishi.api_secret'))) {
            throw new RuntimeException('Jumuishi module credentials are not configured.');
        }

        return Http::acceptJson()->withoutRedirecting()->withHeaders([
            'X-Jumuishi-Module' => config('jumuishi.module_path'),
            'X-Jumuishi-Secret' => config('jumuishi.api_secret'),
        ])->connectTimeout(config('jumuishi.connect_timeout'))->timeout(config('jumuishi.request_timeout'));
    }

    public function exchangeTicket(string $ticket): array
    {
        return $this->client()->post(JumuishiUrl::central(config('jumuishi.sso_exchange_path')), [
            'ticket' => $ticket,
        ])->throw()->json();
    }

    public function syncUser(array $payload): array
    {
        return $this->client()->post(JumuishiUrl::central(config('jumuishi.user_sync_path')), $payload)->throw()->json();
    }
}
