<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'is_encrypted'];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get the typed value, decrypting if needed.
     */
    public function getTypedValue(): mixed
    {
        $raw = $this->is_encrypted && $this->value
            ? Crypt::decryptString($this->value)
            : $this->value;

        return match ($this->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            'json'    => json_decode($raw, true),
            default   => $raw,
        };
    }

    /**
     * Prepare a value for storage, encrypting if needed.
     */
    public static function prepareValue(mixed $value, string $type, bool $encrypt = false): string
    {
        $stored = match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            'json'    => json_encode($value),
            default   => (string) $value,
        };

        return $encrypt ? Crypt::encryptString($stored) : $stored;
    }
}
