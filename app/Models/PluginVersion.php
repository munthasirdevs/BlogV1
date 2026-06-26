<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluginVersion extends Model
{
    protected $fillable = ['plugin_id', 'version', 'changelog', 'compatibility', 'status'];

    protected function casts(): array
    {
        return ['compatibility' => 'json'];
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class);
    }
}
