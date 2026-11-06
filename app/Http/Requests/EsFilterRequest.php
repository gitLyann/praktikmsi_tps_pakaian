<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'preset' => ['nullable', 'string', 'in:7,30,90,bulan_ini'],
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'required_with:from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->filled('from') && $this->filled('to')) {
                $from = Carbon::parse($this->input('from'))->startOfDay();
                $to = Carbon::parse($this->input('to'))->endOfDay();
                if ($from->gt($to)) {
                    $validator->errors()->add('from', 'Tanggal awal tidak boleh setelah tanggal akhir.');
                }
                $max = (int) config('es.max_filter_days', 366);
                if ($from->diffInDays($to) > $max) {
                    $validator->errors()->add('to', "Rentang filter maksimal {$max} hari.");
                }
            }
        });
    }

    /**
     * @return array{from: Carbon, to: Carbon, previous_from: Carbon, previous_to: Carbon, days: int, label: string}
     */
    public function resolvedRange(): array
    {
        $now = now();
        $max = (int) config('es.max_filter_days', 366);

        if ($this->filled('from') && $this->filled('to')) {
            $from = Carbon::parse($this->input('from'))->startOfDay();
            $to = Carbon::parse($this->input('to'))->endOfDay();
        } elseif ($this->input('preset') === 'bulan_ini') {
            $from = $now->copy()->startOfMonth();
            $to = $now->copy()->endOfDay();
        } else {
            $days = in_array((string) $this->input('preset'), ['7', '30', '90'], true)
                ? (int) $this->input('preset')
                : 30;
            $from = $now->copy()->subDays($days - 1)->startOfDay();
            $to = $now->copy()->endOfDay();
        }

        $spanDays = max(1, $from->diffInDays($to) + 1);
        if ($spanDays > $max + 1) { // defensive
            $to = $from->copy()->addDays($max)->endOfDay();
            $spanDays = $max + 1;
        }

        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($spanDays - 1)->startOfDay();

        $label = $this->input('preset') === 'bulan_ini'
            ? 'Bulan ini'
            : ($this->filled('from') ? $from->format('d/m/Y').' - '.$to->format('d/m/Y') : $spanDays.' hari terakhir');

        return compact('from', 'to', 'previousFrom', 'previousTo') + ['days' => $spanDays, 'label' => $label];
    }
}
