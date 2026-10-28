<?php

namespace App\Domain\Access;

use RuntimeException;

/**
 * The actor may not perform this access change (RN-17, RN-18).
 */
final class AccessDenied extends RuntimeException {}
