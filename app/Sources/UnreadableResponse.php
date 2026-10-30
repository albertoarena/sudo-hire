<?php

namespace App\Sources;

use RuntimeException;

/**
 * The source answered, but the response cannot be read: not RSS, no job list.
 */
final class UnreadableResponse extends RuntimeException {}
