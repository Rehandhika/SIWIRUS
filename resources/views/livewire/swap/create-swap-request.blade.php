<div class="max-w-5xl mx-auto space-y-6 pb-20">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Buat Permintaan Tukar Shift</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ajukan permintaan tukar shift dengan anggota lain secara praktis</p>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.badge variant="warning" size="md" class="py-1.5">
                <x-ui.icon name="clock" class="w-4 h-4 mr-1.5" />
                Deadline: 24 Jam Sebelum Shift
            </x-ui.badge>
        </div>
    </div>

    <!-- Progress Steps Indicator -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="grid grid-cols-4 divide-x divide-gray-100 dark:divide-gray-700">
            @php
                $steps = [
                    ['label' => 'Shift Anda', 'icon' => 'calendar', 'active' => true, 'done' => $selectedAssignment],
                    ['label' => 'Cari Shift', 'icon' => 'magnifying-glass', 'active' => $selectedAssignment, 'done' => ($targetDate && $targetSession)],
                    ['label' => 'Pilih User', 'icon' => 'users', 'active' => ($targetDate && $targetSession), 'done' => $selectedTarget],
                    ['label' => 'Konfirmasi', 'icon' => 'check-circle', 'active' => $selectedTarget, 'done' => $reason],
                ];
            @endphp
            @foreach($steps as $index => $step)
                <div class="px-4 py-3 flex flex-col items-center gap-1 transition-colors {{ $step['active'] ? '' : 'opacity-40 grayscale' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all
                        {{ $step['done'] ? 'bg-success-500 text-white shadow-lg shadow-success-200' : ($step['active'] ? 'bg-primary-600 text-white ring-4 ring-primary-50' : 'bg-gray-100 text-gray-400') }}">
                        @if($step['done'])
                            <x-ui.icon name="check" class="w-5 h-5" stroke-width="3" />
                        @else
                            {{ $index + 1 }}
                        @endif
                    </div>
                    <span class="text-[10px] md:text-xs font-bold uppercase tracking-wider {{ $step['active'] ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400' }}">
                        {{ $step['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 space-y-6">
            {{-- Step 1: My Assignments --}}
            <x-ui.card padding="false" class="overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <x-ui.icon name="calendar" class="w-5 h-5 text-primary-600" />
                        1. Pilih Shift Anda
                    </h2>
                    @if($selectedAssignment)
                        <x-ui.badge variant="success" size="sm">Sudah Dipilih</x-ui.badge>
                    @endif
                </div>
                
                <div class="p-6">
                    @if(empty($myAssignments))
                        <x-layout.empty-state 
                            icon="calendar" 
                            title="Tidak ada shift"
                            description="Anda belum memiliki penugasan shift yang akan datang." />
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($myAssignments as $assignment)
                                @php
                                    $deadline = \Carbon\Carbon::parse($assignment->date)->setTimeFromTimeString($assignment->time_start)->subHours(24);
                                    $isPastDeadline = now()->greaterThan($deadline);
                                    $isSelected = $selectedAssignment == $assignment->id;
                                @endphp
                                <button 
                                    type="button"
                                    wire:click="$set('selectedAssignment', {{ $assignment->id }})"
                                    @disabled($isPastDeadline)
                                    class="relative text-left p-4 rounded-xl border-2 transition-all group
                                    {{ $isSelected 
                                        ? 'border-primary-600 bg-primary-50/50 dark:bg-primary-900/10 ring-1 ring-primary-600' 
                                        : ($isPastDeadline ? 'border-gray-100 bg-gray-50 opacity-60 cursor-not-allowed' : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800') }}">
                                    
                                    <div class="flex flex-col gap-2">
                                        <div class="flex justify-between items-start">
                                            <span class="text-xs font-bold text-primary-700 dark:text-primary-400 uppercase tracking-tight">
                                                {{ \Carbon\Carbon::parse($assignment->date)->locale('id')->isoFormat('dddd') }}
                                            </span>
                                            @if($isSelected)
                                                <div class="bg-primary-600 rounded-full p-0.5">
                                                    <x-ui.icon name="check" class="w-3 h-3 text-white" stroke-width="4" />
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-lg font-bold text-gray-900 dark:text-white leading-tight">
                                            {{ \Carbon\Carbon::parse($assignment->date)->format('d M Y') }}
                                        </div>
                                        <div class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                                            <x-ui.icon name="clock" class="w-4 h-4 text-primary-500" />
                                            {{ substr($assignment->time_start, 0, 5) }} - {{ substr($assignment->time_end, 0, 5) }}
                                        </div>
                                        <div class="mt-1">
                                            <x-ui.badge variant="gray" size="sm" class="rounded-md">Sesi {{ $assignment->session }}</x-ui.badge>
                                        </div>
                                    </div>

                                    @if($isPastDeadline)
                                        <div class="mt-3 py-1 px-2 bg-red-50 dark:bg-red-900/20 rounded border border-red-100 dark:border-red-800/30 text-[10px] text-red-600 dark:text-red-400 font-bold uppercase text-center">
                                            Deadline Terlewati
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @error('selectedAssignment')
                        <p class="mt-3 text-xs text-danger-600 font-bold flex items-center gap-1.5 uppercase">
                            <x-ui.icon name="exclamation-circle" class="w-4 h-4" /> {{ $message }}
                        </p>
                    @enderror
                </div>
            </x-ui.card>

            {{-- Step 2: Target Shift --}}
            <x-ui.card padding="false" class="overflow-hidden {{ !$selectedAssignment ? 'opacity-50 pointer-events-none' : '' }}">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <x-ui.icon name="magnifying-glass" class="w-5 h-5 text-primary-600" />
                        2. Pilih Shift Target
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Tanggal Target</label>
                            <x-ui.input 
                                type="date" 
                                wire:model.live="targetDate"
                                name="targetDate"
                                :min="today()->format('Y-m-d')"
                                :error="$errors->first('targetDate')" />
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Sesi Target</label>
                            <x-ui.select 
                                wire:model.live="targetSession"
                                name="targetSession"
                                placeholder="Pilih Sesi"
                                :options="$sessionOptions"
                                :error="$errors->first('targetSession')" />
                        </div>
                    </div>

                    @if($targetDate && $targetSession)
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-800/30 flex items-center gap-3">
                            <div class="bg-blue-600 rounded-full p-1.5 shadow-sm">
                                <x-ui.icon name="information-circle" class="w-5 h-5 text-white" />
                            </div>
                            <div class="text-sm text-blue-800 dark:text-blue-300 font-medium">
                                Mencari anggota yang bertugas pada <strong>{{ \Carbon\Carbon::parse($targetDate)->locale('id')->isoFormat('dddd, D MMMM Y') }}</strong> Sesi <strong>{{ $targetSession }}</strong>
                            </div>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            {{-- Step 3: Target User --}}
            @if($targetDate && $targetSession)
                <x-ui.card padding="false" class="overflow-hidden animate-in slide-in-from-bottom-4 duration-300">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <x-ui.icon name="users" class="w-5 h-5 text-primary-600" />
                            3. Pilih User Target
                        </h2>
                    </div>
                    <div class="p-6">
                        @if(empty($availableTargets))
                            <x-layout.empty-state 
                                icon="user-group" 
                                title="Tidak ada user ditemukan"
                                description="Tidak ada anggota aktif yang bertugas pada jadwal tersebut." />
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($availableTargets as $target)
                                    <button 
                                        type="button"
                                        wire:click="$set('selectedTarget', {{ $target['id'] }})"
                                        class="text-left p-4 rounded-xl border-2 transition-all flex items-center justify-between group
                                        {{ $selectedTarget == $target['id'] 
                                            ? 'border-primary-600 bg-primary-50/50 dark:bg-primary-900/10 ring-1 ring-primary-600' 
                                            : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800' }}">
                                        
                                        <div class="flex items-center gap-3">
                                            <x-ui.avatar :name="$target['name']" size="md" />
                                            <div>
                                                <p class="font-bold text-gray-900 dark:text-white">{{ $target['name'] }}</p>
                                                <p class="text-xs text-gray-500 font-medium">{{ $target['nim'] }}</p>
                                            </div>
                                        </div>
                                        
                                        @if($selectedTarget == $target['id'])
                                            <div class="bg-primary-600 rounded-full p-0.5">
                                                <x-ui.icon name="check" class="w-3 h-3 text-white" stroke-width="4" />
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @error('selectedTarget')
                            <p class="mt-3 text-xs text-danger-600 font-bold flex items-center gap-1.5 uppercase italic">
                                <x-ui.icon name="exclamation-circle" class="w-4 h-4" /> {{ $message }}
                            </p>
                        @enderror
                    </div>
                </x-ui.card>
            @endif

            {{-- Step 4: Reason --}}
            @if($selectedTarget)
                <x-ui.card padding="false" class="overflow-hidden animate-in slide-in-from-bottom-4 duration-300">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <x-ui.icon name="chat-bubble-left-right" class="w-5 h-5 text-primary-600" />
                            4. Alasan Tukar
                        </h2>
                    </div>
                    <div class="p-6">
                        <x-ui.textarea 
                            wire:model.live="reason"
                            label="Berikan alasan yang jelas"
                            placeholder="Contoh: Ada jadwal praktikum pengganti atau urusan mendesak..."
                            rows="4"
                            :error="$errors->first('reason')"
                            help="Minimal 10 karakter ({{ strlen($reason) ?? 0 }}/500)" />
                    </div>
                </x-ui.card>
            @endif
        </div>

        {{-- Sidebar Summary --}}
        <div class="lg:sticky lg:top-6 space-y-6">
            <x-ui.card padding="false" class="overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-primary-600 flex items-center gap-2 shadow-sm">
                    <x-ui.icon name="clipboard-document-list" class="w-5 h-5 text-white" />
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Ringkasan</h2>
                </div>
                <div class="p-6 space-y-6">
                    {{-- Requester Slot --}}
                    <div class="space-y-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Shift Anda</p>
                        @php $mySel = collect($myAssignments)->where('id', $selectedAssignment)->first(); @endphp
                        @if($mySel)
                            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($mySel->date)->format('d M Y') }}</p>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">Sesi {{ $mySel->session }} ({{ substr($mySel->time_start, 0, 5) }})</p>
                            </div>
                        @else
                            <div class="text-xs text-gray-400 italic">Belum dipilih</div>
                        @endif
                    </div>

                    {{-- Target Slot --}}
                    <div class="space-y-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Ditukar Dengan</p>
                        <div class="flex justify-center">
                            <div class="bg-primary-100 text-primary-600 rounded-full p-1.5 shadow-sm ring-4 ring-primary-50">
                                <x-ui.icon name="arrow-path" class="w-5 h-5" stroke-width="3" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">Shift Target</p>
                        @if($targetDate && $targetSession && $selectedTarget)
                            @php $targetU = collect($availableTargets)->where('id', $selectedTarget)->first(); @endphp
                            <div class="p-3 bg-primary-50 dark:bg-primary-900/10 rounded-lg border border-primary-100 dark:border-primary-800/30 text-right">
                                <p class="text-sm font-bold text-primary-900 dark:text-primary-300">{{ \Carbon\Carbon::parse($targetDate)->format('d M Y') }}</p>
                                <p class="text-xs text-primary-700 dark:text-primary-400 font-medium mt-0.5">Sesi {{ $targetSession }}</p>
                                <p class="text-xs font-bold text-gray-900 dark:text-white mt-2 border-t border-primary-100 dark:border-primary-800 pt-2">
                                    {{ $targetU['name'] }}
                                </p>
                            </div>
                        @else
                            <div class="text-xs text-gray-400 italic text-right">Belum dipilih</div>
                        @endif
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-col gap-3">
                        <x-ui.button 
                            type="button"
                            wire:click="validateSwapRequest"
                            variant="primary"
                            class="w-full py-3 text-sm font-bold shadow-lg shadow-primary-200"
                            :disabled="!$selectedAssignment || !$selectedTarget || strlen($reason) < 10"
                            wire:loading.attr="disabled"
                            wire:target="validateSwapRequest">
                            Lanjutkan
                        </x-ui.button>
                        <x-ui.button 
                            type="button"
                            wire:click="resetForm"
                            variant="white"
                            class="w-full py-2.5 text-xs font-bold">
                            Reset Form
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>

            <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-xl border border-amber-100 dark:border-amber-800/30">
                <div class="flex gap-3">
                    <x-ui.icon name="exclamation-triangle" class="w-5 h-5 text-amber-600 shrink-0" />
                    <div class="text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed">
                        <p class="font-bold uppercase tracking-wider mb-1">Peringatan Penting:</p>
                        <ul class="list-disc ml-4 space-y-1">
                            <li>Permintaan akan dikirim ke user target.</li>
                            <li>Tunggu user target menyetujui.</li>
                            <li>Admin koperasi akan memproses final approval.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Robust Confirmation Modal -->
    @if($showConfirmation)
        <div class="fixed inset-0 z-[60] overflow-y-auto" x-data x-on:keydown.escape.window="$wire.set('showConfirmation', false)">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" wire:click="$wire.set('showConfirmation', false)"></div>
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100 dark:border-gray-700">
                    <div class="px-6 pt-6 pb-4">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30 mb-4">
                            <x-ui.icon name="paper-airplane" class="h-7 w-7 text-primary-600 dark:text-primary-400" stroke-width="2" />
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Konfirmasi Permintaan</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Apakah data yang Anda masukkan sudah benar?</p>
                        </div>

                        @php
                            $myAs = collect($myAssignments)->where('id', $selectedAssignment)->first();
                            $targetU = collect($availableTargets)->where('id', $selectedTarget)->first();
                        @endphp

                        <div class="mt-6 space-y-3 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 text-sm">
                            <div class="flex justify-between items-start py-1">
                                <span class="text-gray-500">Shift Anda:</span>
                                <span class="font-bold text-gray-900 dark:text-white text-right">
                                    {{ \Carbon\Carbon::parse($myAs->date)->format('d/m/Y') }}<br>
                                    <span class="text-xs text-gray-500 font-normal">Sesi {{ $myAs->session }}</span>
                                </span>
                            </div>
                            <div class="flex justify-between items-start py-1">
                                <span class="text-gray-500">Shift Target:</span>
                                <span class="font-bold text-gray-900 dark:text-white text-right">
                                    {{ \Carbon\Carbon::parse($targetDate)->format('d/m/Y') }}<br>
                                    <span class="text-xs text-gray-500 font-normal">Sesi {{ $targetSession }}</span>
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-500">Target User:</span>
                                <span class="font-bold text-primary-600 dark:text-primary-400">{{ $targetU['name'] }}</span>
                            </div>
                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Alasan:</p>
                                <p class="text-gray-700 dark:text-gray-300 italic">"{{ $reason }}"</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-3 border-t border-gray-100 dark:border-gray-700">
                        <x-ui.button 
                            wire:click="createSwapRequest"
                            variant="primary"
                            class="w-full sm:w-auto px-8"
                            wire:loading.attr="disabled"
                            wire:target="createSwapRequest">
                            Kirim Sekarang
                        </x-ui.button>
                        <x-ui.button 
                            wire:click="$wire.set('showConfirmation', false)"
                            variant="white"
                            class="w-full sm:w-auto">
                            Periksa Kembali
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
