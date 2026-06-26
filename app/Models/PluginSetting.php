<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluginSetting extends Model
{
    protected $fillable = ['plugin_id', 'tenant_id', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }
}
