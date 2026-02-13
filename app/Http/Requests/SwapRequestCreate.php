<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwapRequestCreate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Ketua', 'Wakil Ketua', 'BPH', 'Anggota']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'original_schedule_assignment_id' => [
                'required',
                'integer',
                Rule::exists('schedule_assignments', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id())
                        ->where('date', '>=', today()->addDays(config('siwirus.swap.min_advance_notice_days', 2)))
                        ->where('status', 'scheduled');
                }),
            ],
            'target_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('status', 'active')
                        ->whereHas('roles', function ($subQuery) {
                            $subQuery->whereIn('name', ['Super Admin', 'Ketua', 'Wakil Ketua', 'BPH', 'Anggota']);
                        });
                }),
            ],
            'target_schedule_assignment_id' => [
                'nullable',
                'integer',
                Rule::exists('schedule_assignments', 'id')->where(function ($query) {
                    $query->where('user_id', $this->target_user_id)
                        ->where('date', '>=', today()->addDays(config('siwirus.swap.min_advance_notice_days', 2)))
                        ->where('status', 'scheduled');
                }),
            ],
            'reason' => 'required|string|max:1000|min:10',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'original_schedule_assignment_id.required' => 'Jadwal asli harus dipilih.',
            'original_schedule_assignment_id.exists' => 'Jadwal asli tidak valid.',
            'target_user_id.required' => 'User target harus dipilih.',
            'target_user_id.exists' => 'User target tidak valid atau tidak aktif.',
            'target_schedule_assignment_id.exists' => 'Jadwal target tidak valid.',
            'reason.required' => 'Alasan penukaran harus diisi.',
            'reason.min' => 'Alasan minimal 10 karakter.',
            'reason.max' => 'Alasan maksimal 1000 karakter.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
        ];
    }

    /**
     * Get validated and sanitized data.
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();

        // Sanitize text inputs to prevent XSS
        $data['reason'] = strip_tags($data['reason']);
        $data['reason'] = trim($data['reason']);

        if (isset($data['notes'])) {
            $data['notes'] = strip_tags($data['notes']);
            $data['notes'] = trim($data['notes']);
        }

        return $data;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Prevent self-swap
            if ($this->target_user_id == auth()->id()) {
                $validator->errors()->add('target_user_id', 'Tidak dapat menukar shift dengan diri sendiri.');
            }

            // Check for duplicate swap requests
            if ($this->original_schedule_assignment_id) {
                $existingSwap = \App\Models\SwapRequest::where('original_schedule_assignment_id', $this->original_schedule_assignment_id)
                    ->whereIn('status', ['pending', 'target_approved', 'admin_approved'])
                    ->exists();

                if ($existingSwap) {
                    $validator->errors()->add('original_schedule_assignment_id', 'Jadwal ini sudah memiliki permintaan penukaran yang aktif.');
                }
            }
        });
    }
}
