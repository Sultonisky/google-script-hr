<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MprRequestor extends Model
{
    protected $table = 'mpr_requestors';

    protected $fillable = [
        'requestor_id',
        'email',
        'username',
        'full_name',
        'job_position',
        'role',
        'status',
        'password_hash',
        'last_login',
        'created_by',
    ];

    protected $hidden = [
        'password_hash',
    ];
}
