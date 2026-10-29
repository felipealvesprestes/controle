<?php

namespace App\Domain\Address;

use RuntimeException;

/**
 * The CEP directory did not answer, or answered something unusable.
 */
final class AddressLookupUnavailable extends RuntimeException {}
