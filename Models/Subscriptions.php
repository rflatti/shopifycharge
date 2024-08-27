<?php

namespace RFlatti\ShopifyPlans\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Subscriptions extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'subscriptions';

}
