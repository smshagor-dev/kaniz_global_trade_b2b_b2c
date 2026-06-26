<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCompanyCategory extends Model
{
    protected $table = 'b2b_company_categories';

    protected $fillable = [
        'b2b_company_id',
        'category_id',
    ];

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
