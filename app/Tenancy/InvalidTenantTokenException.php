<?php

namespace App\Tenancy;

use RuntimeException;

/**
 * The single exception for every way a signed tenant token can fail
 * verification: bad signature, wrong purpose, or expired. Deliberately one
 * type, so the HTTP response cannot let a caller distinguish which happened.
 */
class InvalidTenantTokenException extends RuntimeException {}
