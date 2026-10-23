<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRoleRequest extends FormRequest
{
    /**
     * Otorisasi "bolehkah user meminta perubahan role" sudah ditangani
     * middleware can:manage-roles pada route. Di sini tidak digandakan.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // 'admin' sengaja tidak tersedia (BR-03).
        return ['role' => ['required', 'string',
            Rule::in(['customer', 'staff'])]];
    }
}
