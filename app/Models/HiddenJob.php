<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A tombstone of a hidden job: one source key and the fingerprint of the job.
 * JobStore does not store a job with this key or this fingerprint again.
 *
 * @property int $job_id
 * @property string $source
 * @property string $source_job_id
 * @property string $fingerprint
 */
#[Fillable(['job_id', 'source', 'source_job_id', 'fingerprint'])]
class HiddenJob extends Model {}
