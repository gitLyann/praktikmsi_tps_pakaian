<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EisWeightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'matrix' => ['required', 'array', 'min:1'],
            'matrix.*' => ['required', 'array', 'min:1'],
            'matrix.*.*' => ['required', 'numeric', 'min:0.001'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $matrix = $this->input('matrix', []);
            $size = count($matrix);

            foreach ($matrix as $row) {
                if (count($row) !== $size) {
                    $validator->errors()->add('matrix', 'Matriks AHP harus berbentuk persegi.');

                    return;
                }
            }
        });
    }
}
