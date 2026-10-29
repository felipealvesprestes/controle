<?php

namespace App\Application\Provider;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Provider\CompanyDetails;
use App\Domain\Provider\ProviderProfileRepository;
use App\Domain\Provider\ProviderRuleViolation;
use App\Domain\Provider\TaxRegime;
use App\Domain\Shared\Cnpj;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Changes the company data used on the report and on the invoice (F-02).
 */
final class UpdateCompanyDetails
{
    /** Brazilian states, for the address. */
    public const array STATES = ['AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO'];

    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ProviderProfileRepository $profiles,
        private readonly AuditTrail $audit,
    ) {}

    /**
     * @param  array{legalName: string, tradeName: string, cnpj: string, municipalRegistration: string, street: string, number: string, complement: string, district: string, city: string, state: string, zipCode: string}  $input
     */
    public function handle(int $actorId, array $input): CompanyDetails
    {
        if (! $this->authorizer->allows($actorId, 'settings.company.edit')) {
            throw new AccessDenied('You may not change the company details.');
        }

        $text = array_map(static fn (string $value): string => trim((string) preg_replace('/\s+/u', ' ', $value)), $input);

        if ($text['legalName'] === '') {
            throw new ProviderRuleViolation('legalName', 'Enter the legal name.');
        }

        foreach (['legalName' => 255, 'tradeName' => 255, 'street' => 255, 'complement' => 100, 'district' => 100, 'city' => 100, 'number' => 20, 'municipalRegistration' => 30] as $field => $limit) {
            if (mb_strlen($text[$field]) > $limit) {
                throw new ProviderRuleViolation($field, 'Too long for this field.');
            }
        }

        try {
            $cnpj = Cnpj::fromInput($text['cnpj']);
        } catch (InvalidArgumentException) {
            throw new ProviderRuleViolation('cnpj', 'Enter a valid CNPJ.');
        }

        if ($text['municipalRegistration'] !== '' && preg_match('/^[0-9A-Za-z.\/-]+$/', $text['municipalRegistration']) !== 1) {
            throw new ProviderRuleViolation('municipalRegistration', 'Use only letters, digits, dots, slashes and hyphens.');
        }

        $state = strtoupper($text['state']);

        if ($state !== '' && ! in_array($state, self::STATES, true)) {
            throw new ProviderRuleViolation('state', 'Choose a state.');
        }

        $zipCode = (string) preg_replace('/\D/', '', $text['zipCode']);

        if ($text['zipCode'] !== '' && strlen($zipCode) !== 8) {
            throw new ProviderRuleViolation('zipCode', 'Enter the 8 digits of the CEP.');
        }

        $details = new CompanyDetails(
            legalName: $text['legalName'],
            tradeName: $text['tradeName'],
            cnpj: $cnpj,
            municipalRegistration: $text['municipalRegistration'],
            taxRegime: TaxRegime::SimplesMeEpp,
            street: $text['street'],
            number: $text['number'],
            complement: $text['complement'],
            district: $text['district'],
            city: $text['city'],
            state: $state,
            zipCode: $zipCode,
        );

        DB::transaction(function () use ($actorId, $details): void {
            $before = $this->profiles->companyDetails();
            $profileId = $this->profiles->saveCompanyDetails($details);
            $this->audit->record($actorId, 'provider.company_details.updated', 'provider_profile', $profileId, $before->toArray(), $details->toArray());
        });

        return $details;
    }
}
