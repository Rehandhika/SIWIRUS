<div class="space-y-6">
    <div class="max-w-3xl mx-auto">
        <x-layout.page-header 
            title="Ajukan Cuti/Izin"
            description="Buat pengajuan cuti atau izin dengan mengisi formulir berikut" />

        <form wire:submit="submit">
            <x-ui.card padding="true">
                <div class="space-y-6">
                {{-- Leave Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Jenis Cuti/Izin *</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <button type="button" wire:click="$set('leave_type', 'permission')" 
                                @class([
                                    'p-4 border-2 rounded-lg transition text-center',
                                    'border-blue-600 bg-blue-50 text-blue-600' => $leave_type === 'permission',
                                    'border-gray-300 text-gray-700 hover:border-gray-400' => $leave_type !== 'permission',
                                ])>
                            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Izin</span>
                        </button>
                        
                        <button type="button" wire:click="$set('leave_type', 'sick')" 
                                @class([
                                    'p-4 border-2 rounded-lg transition text-center',
                                    'border-red-600 bg-red-50 text-red-600' => $leave_type === 'sick',
                                    'border-gray-300 text-gray-700 hover:border-gray-400' => $leave_type !== 'sick',
                                ])>
                            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Sakit</span>
                        </button>
                        
                        <button type="button" wire:click="$set('leave_type', 'emergency')" 
                                @class([
                                    'p-4 border-2 rounded-lg transition text-center',
                                    'border-yellow-600 bg-yellow-50 text-yellow-600' => $leave_type === 'emergency',
                                    'border-gray-300 text-gray-700 hover:border-gray-400' => $leave_type !== 'emergency',
                                ])>
                            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span class="text-sm font-medium">Darurat</span>
                        </button>
                        
                        <button type="button" wire:click="$set('leave_type', 'other')" 
                                @class([
                                    'p-4 border-2 rounded-lg transition text-center',
                                    'border-blue-600 bg-blue-50 text-blue-600' => $leave_type === 'other',
                                    'border-gray-300 text-gray-700 hover:border-gray-400' => $leave_type !== 'other',
                                ])>
                            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span class="text-sm font-medium">Lainnya</span>
                        </button>
                    </div>
                    @error('leave_type') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input 
                        type="date"
                        name="start_date"
                        label="Tanggal Mulai"
                        wire:model.live="start_date"
                        required
                        :error="$errors->first('start_date')" />

                    <x-ui.input 
                        type="date"
                        name="end_date"
                        label="Tanggal Selesai"
                        wire:model.live="end_date"
                        required
                        :error="$errors->first('end_date')" />
                </div>

                @if($totalDays > 0)
                    <x-ui.alert variant="info" :icon="true">
                        Total durasi: <strong>{{ $totalDays }} hari</strong>
                    </x-ui.alert>
                @endif

                {{-- Affected Schedules --}}
                @if(count($affectedSchedules) > 0)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Jadwal yang Akan Terdampak
                            <span class="text-gray-500 font-normal">({{ count($affectedSchedules) }} jadwal)</span>
                        </label>
                        <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 max-h-64 overflow-y-auto">
                            <div class="space-y-2">
                                @foreach($affectedSchedules as $schedule)
                                    <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $schedule['date'] }}</p>
                                                <p class="text-xs text-gray-500">{{ $schedule['session_name'] }} ({{ $schedule['time'] }})</p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($schedule['status'] === 'scheduled') bg-blue-100 text-blue-800
                                            @elseif($schedule['status'] === 'excused') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($schedule['status']) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Jadwal-jadwal ini akan diubah statusnya menjadi "excused" jika permohonan disetujui
                        </p>
                    </div>
                @elseif($start_date && $end_date)
                    <x-ui.alert variant="warning" :icon="true">
                        Tidak ada jadwal yang terdampak pada periode yang dipilih.
                    </x-ui.alert>
                @endif

                <x-ui.textarea 
                    name="reason"
                    label="Alasan"
                    wire:model="reason"
                    rows="4"
                    placeholder="Jelaskan alasan cuti/izin Anda (minimal 10 karakter)"
                    required
                    :error="$errors->first('reason')"
                    help="{{ strlen($reason) }}/500 karakter" />

                {{-- Attachment --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Lampiran (Opsional)
                        <span class="text-gray-500 font-normal">- Surat keterangan dokter, dll</span>
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition">
                        <div class="space-y-1 text-center">
                            @if ($attachment)
                                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-gray-600">{{ $attachment->getClientOriginalName() }}</p>
                                <button type="button" wire:click="$set('attachment', null)" class="text-sm text-red-600 hover:text-red-800">
                                    Hapus file
                                </button>
                            @else
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                        <span>Upload file</span>
                                        <input wire:model="attachment" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf">
                                    </label>
                                    <p class="pl-1">atau drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">JPG, PNG, PDF max 2MB</p>
                            @endif
                        </div>
                    </div>
                    @error('attachment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="attachment" class="text-sm text-blue-600 mt-2">
                        Uploading...
                    </div>
                </div>

                <x-ui.alert variant="warning" :icon="true">
                    <div>
                        <p class="font-medium mb-1">Catatan Penting:</p>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            <li>Pengajuan akan ditinjau oleh admin</li>
                            <li>Pastikan mengisi alasan dengan jelas</li>
                            <li>Untuk sakit lebih dari 2 hari, lampirkan surat keterangan dokter</li>
                            <li>Pengajuan darurat akan diprioritaskan</li>
                        </ul>
                    </div>
                </x-ui.alert>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <x-ui.button 
                        variant="white" 
                        :href="route('admin.leave.my-requests')">
                        Batal
                    </x-ui.button>
                    <x-ui.button 
                        type="submit" 
                        variant="primary"
                        :loading="false"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">Ajukan Permohonan</span>
                        <span wire:loading wire:target="submit">Mengirim...</span>
                    </x-ui.button>
                </div>
                </div>
            </x-ui.card>
        </form>
    </div>

    <div wire:loading.flex wire:target="submit" class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50">
        <x-ui.card padding="true">
            <div class="text-center">
                <x-ui.spinner size="lg" class="mx-auto mb-4" />
                <p class="text-gray-700 font-medium">Memproses pengajuan...</p>
            </div>
        </x-ui.card>
    </div>
</div>
