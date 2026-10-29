<?php

namespace App\Domain\Address;

use RuntimeException;

/**
 * A CEP lookup that is not attempted; the message is a translation key.
 */
final class ZipCodeLookupRefused extends RuntimeException {}
