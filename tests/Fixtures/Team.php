<?php

namespace Crumbls\Subscriptions\Tests\Fixtures;

use Crumbls\Subscriptions\Traits\HasPlanSubscriptions;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasPlanSubscriptions;

    protected $fillable = ['name'];
}
