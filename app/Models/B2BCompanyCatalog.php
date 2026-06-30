<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCompanyCatalog extends Model
{
    protected $table = 'b2b_company_catalogs';

    protected $fillable = [
        'b2b_company_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'pdf_file',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function coverUpload()
    {
        return $this->belongsTo(Upload::class, 'cover_image');
    }

    public function pdfUpload()
    {
        return $this->belongsTo(Upload::class, 'pdf_file');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'b2b_company_catalog_id');
    }
}
