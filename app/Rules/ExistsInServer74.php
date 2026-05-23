<?php

namespace App\Rules;

use App\Models\Pegawai;
use Illuminate\Contracts\Validation\Rule;

class ExistsInServer74 implements Rule
{
    protected $table;
    protected $column;
    protected $message;

    /**
     * Create a new rule instance.
     *
     * @param string $table
     * @param string $column
     * @param string|null $message
     */
    public function __construct($table = 'pegawai', $column = 'nik', $message = null)
    {
        $this->table = $table;
        $this->column = $column;
        $this->message = $message;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true; // Skip jika kosong (gunakan required untuk mandatory)
        }

        // Gunakan connection server_74
        return \DB::connection('server_74')
            ->table($this->table)
            ->where($this->column, $value)
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message ?? "The selected :attribute is invalid.";
    }
}
