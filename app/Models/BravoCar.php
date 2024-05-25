<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BravoCar extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'vehicle_type',
        'vehicle_year',
        'content',
        'image_id',
        'banner_image_id',
        'location_id',
        'address',
        'map_lat',
        'map_lng',
        'map_zoom',
        'is_featured',
        'gallery',
        'video',
        'faqs',
        'number',
        'price',
        'sale_price',
        'prices',
        'sale_prices',
        'is_instant',
        'enable_extra_price',
        'extra_price',
        'discount_by_days',
        'passenger',
        'gear',
        'baggage',
        'door',
        'status',
        'default_state',
        'create_user',
        'update_user',
        'deleted_at',
        'review_score',
        'ical_import_url',
        'enable_service_fee',
        'service_fee',
        'min_day_before_booking',
        'min_day_stays',
    ];
  
}
