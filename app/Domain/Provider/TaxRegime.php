<?php

namespace App\Domain\Provider;

/**
 * The provider's tax regime; the only one supported is Simples Nacional for ME/EPP (fiscal/nfse.md).
 */
enum TaxRegime: string
{
    case SimplesMeEpp = 'simples_me_epp';
}
