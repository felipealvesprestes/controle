<?php

namespace App\Infrastructure\Address;

use App\Domain\Address\AddressLookup;
use App\Domain\Address\AddressLookupUnavailable;
use App\Domain\Address\AddressSuggestion;
use App\Domain\Address\BrazilianStates;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * ViaCEP (viacep.com.br): free, no key. Found addresses are cached for 30 days (ADR-0013).
 */
final class ViaCepAddressLookup implements AddressLookup
{
    public function find(string $zipCode): ?AddressSuggestion
    {
        if (preg_match('/^\d{8}$/', $zipCode) !== 1) {
            throw new InvalidArgumentException('A CEP has exactly 8 digits.');
        }

        /** @var array{street: string, district: string, city: string, state: string}|null $cached */
        $cached = Cache::get('zip-code:'.$zipCode);

        if ($cached !== null) {
            return new AddressSuggestion(...$cached);
        }

        $suggestion = $this->request($zipCode);

        if ($suggestion !== null) {
            Cache::put('zip-code:'.$zipCode, ['street' => $suggestion->street, 'district' => $suggestion->district, 'city' => $suggestion->city, 'state' => $suggestion->state], now()->addDays(30));
        }

        return $suggestion;
    }

    private function request(string $zipCode): ?AddressSuggestion
    {
        try {
            $response = Http::baseUrl(config()->string('services.viacep.url'))
                ->acceptJson()
                ->withoutRedirecting()
                ->connectTimeout(2)
                ->timeout(3)
                ->retry(2, 200, throw: false)
                ->get("ws/{$zipCode}/json/");
        } catch (ConnectionException $exception) {
            throw new AddressLookupUnavailable('CEP directory unreachable.', previous: $exception);
        }

        // ViaCEP answers 400 for a malformed CEP and {"erro": "true"} for one that does not exist.
        if (! $response->ok() || ! is_array($response->json())) {
            throw new AddressLookupUnavailable("CEP directory answered {$response->status()}.");
        }

        if ($response->json('erro') !== null) {
            return null;
        }

        // The answer is outside data: keep only short strings and a known state.
        $field = static fn (string $key, int $limit): string => mb_substr(trim(is_string($value = $response->json($key)) ? $value : ''), 0, $limit);
        $state = strtoupper($field('uf', 2));

        if (! in_array($state, BrazilianStates::CODES, true) || $field('localidade', 100) === '') {
            throw new AddressLookupUnavailable('CEP directory answered without city or state.');
        }

        return new AddressSuggestion(
            street: $field('logradouro', 255),
            district: $field('bairro', 100),
            city: $field('localidade', 100),
            state: $state,
        );
    }
}
