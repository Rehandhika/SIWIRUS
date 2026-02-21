<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveRequestCreate extends FormRequest
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
        $minAdvanceDays = config('app-settings.leave.min_advance_notice_days', 3);
        $leaveType = $this->input('leave_type_id'); // Assuming leave_type_id logic needs check against ID or slug. Wait, input is leave_type slug in create-leave-request blade. Let's check blade. Blade uses wire:click="$set('leave_type', 'permission')". So the input name is 'leave_type'.
        // Correction: The Request class validates 'leave_type_id' but the Livewire component uses 'leave_type'.
        // Let's assume this Request is used by Livewire via rules().
        // Actually, Livewire components usually define rules property directly.
        // Checking LeaveRequestCreate.php again... It extends FormRequest.
        // Wait, Livewire component `App\Livewire\Leave\CreateLeaveRequest` uses `$rules` property but also `validate()` which uses standard rules.
        // BUT `LeaveRequestCreate` is a FormRequest class, usually for Controllers.
        // Livewire component `CreateLeaveRequest.php` has its OWN rules.
        // I need to modify `App\Livewire\Leave\CreateLeaveRequest.php`, NOT `App\Http\Requests\LeaveRequestCreate.php` if Livewire is used.
        // Let's check `App\Livewire\Leave\CreateLeaveRequest.php` content again.
        // It has `protected $rules = [...]`.
        // So I should modify the Livewire component.
        
        return [
            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists('leave_types', 'id')->where('is_active', true),
            ],
            'date_from' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.now()->addMonths(3)->format('Y-m-d'),
            ],
            'date_to' => [
                'required',
                'date',
                'after_or_equal:date_from',
                'before_or_equal:'.now()->addMonths(3)->format('Y-m-d'),
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:5120',
            ],
            'emergency_contact' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        $minAdvanceDays = config('app-settings.leave.min_advance_notice_days', 3);

        return [
            'leave_type_id.required' => 'Jenis cuti harus dipilih.',
            'leave_type_id.exists' => 'Jenis cuti tidak valid.',
            'date_from.required' => 'Tanggal mulai harus diisi.',
            'date_from.after_or_equal' => "Tanggal mulai tidak boleh di masa lalu.",
            'date_from.before_or_equal' => 'Pengajuan cuti maksimal 3 bulan ke depan.',
            'date_to.required' => 'Tanggal selesai harus diisi.',
            'date_to.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            'date_to.before_or_equal' => 'Pengajuan cuti maksimal 3 bulan ke depan.',
            'reason.required' => 'Alasan cuti harus diisi.',
            'reason.min' => 'Alasan minimal 10 karakter.',
            'reason.max' => 'Alasan maksimal 1000 karakter.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
            'attachment.file' => 'Lampiran harus berupa file.',
            'attachment.mimes' => 'Format lampiran harus pdf, doc, docx, jpg, jpeg, atau png.',
            'attachment.max' => 'Ukuran lampiran maksimal 5MB.',
            'emergency_contact.max' => 'Kontak darurat maksimal 255 karakter.',
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

        if (isset($data['emergency_contact'])) {
            $data['emergency_contact'] = strip_tags($data['emergency_contact']);
            $data['emergency_contact'] = trim($data['emergency_contact']);
        }

        // Calculate days
        $dateFrom = \Carbon\Carbon::parse($data['date_from']);
        $dateTo = \Carbon\Carbon::parse($data['date_to']);
        $data['days'] = $dateFrom->diffInDays($dateTo) + 1;

        return $data;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $userId = auth()->id();
            $dateFrom = $this->date_from;
            $dateTo = $this->date_to;

            if ($dateFrom && $dateTo) {
                // Check for overlapping leave requests
                $existingLeave = \App\Models\LeaveRequest::where('user_id', $userId)
                    ->whereIn('status', ['pending', 'approved'])
                    ->where(function ($query) use ($dateFrom, $dateTo) {
                        $query->whereBetween('date_from', [$dateFrom, $dateTo])
                            ->orWhereBetween('date_to', [$dateFrom, $dateTo])
                            ->orWhere(function ($subQuery) use ($dateFrom, $dateTo) {
                                $subQuery->where('date_from', '<=', $dateFrom)
                                    ->where('date_to', '>=', $dateTo);
                            });
                    })
                    ->exists();

                if ($existingLeave) {
                    $validator->errors()->add('date_from', 'Anda sudah memiliki pengajuan cuti pada periode tersebut.');
                }



                // Check for existing schedules during leave period
                $hasSchedules = \App\Models\ScheduleAssignment::where('user_id', $userId)
                    ->whereBetween('date', [$dateFrom, $dateTo])
                    ->where('status', 'scheduled')
                    ->exists();

                if (! $hasSchedules) {
                    $validator->errors()->add('date_from', 'Tidak ada jadwal kerja pada periode yang dipilih.');
                }
            }
        });
    }
}
