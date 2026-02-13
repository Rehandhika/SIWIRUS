<div class="max-w-4xl mx-auto p-4 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Ketersediaan Jadwal</h1>
            <p class="text-sm text-gray-500 mt-1">Pilih sesi yang Anda bisa hadir</p>
        </div>
        @if($this->isSubmitted)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-full bg-green-100 text-green-700">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Sudah Dikirim
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-full bg-amber-100 text-amber-700">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                Belum Dikirim
            </span>
        @endif
    </div>

    {{-- Week Navigation --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <button wire:click="$set('weekOffset', {{ $weekOffset - 1 }})" 
                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed" 
                    @disabled($weekOffset <= 0)>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            
            <div class="text-center">
                <select wire:model.live="weekOffset" class="text-sm font-medium border-0 bg-transparent focus:ring-0 text-center cursor-pointer">
                    @for($i = 0; $i <= 4; $i++)
                        <option value="{{ $i }}">
                            {{ $i === 0 ? 'Minggu Ini' : ($i === 1 ? 'Minggu Depan' : "$i Minggu Lagi") }}
                        </option>
                    @endfor
                </select>
                <p class="text-sm text-gray-500 mt-1">{{ $this->weekLabel }}</p>
            </div>
            
            <button wire:click="$set('weekOffset', {{ $weekOffset + 1 }})" 
                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed" 
                    @disabled($weekOffset >= 4)>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <span class="text-2xl font-bold text-blue-600">{{ $this->totalSelected }}</span>
            <p class="text-sm text-gray-500 mt-1">Sesi Dipilih</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <span class="text-2xl font-bold text-green-600">{{ $this->totalSelected * 3 }}</span>
            <p class="text-sm text-gray-500 mt-1">Jam</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <span class="text-2xl font-bold text-blue-600">{{ $this->totalSelected }}/12</span>
            <p class="text-sm text-gray-500 mt-1">Slot</p>
        </div>
    </div>

    {{-- Availability Grid --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Hari</th>
                    @foreach($this->sessions as $key => $time)
                        <th class="py-3 px-3 text-center text-sm font-semibold text-gray-700">
                            <div>Sesi {{ $key }}</div>
                            <div class="text-xs font-normal text-gray-400 mt-0.5">{{ $time }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($this->days as $dayKey => $dayLabel)
                    <tr class="hover:bg-gray-50/50">
                        <td class="py-4 px-4">
                            @if($this->canEdit)
                                <button type="button" 
                                        wire:click="toggleDay('{{ $dayKey }}')"
                                        class="font-medium text-gray-900 hover:text-blue-600 transition-colors">
                                    {{ $dayLabel }}
                                </button>
                            @else
                                <span class="font-medium text-gray-900">{{ $dayLabel }}</span>
                            @endif
                        </td>
                        @foreach(array_keys($this->sessions) as $sessionKey)
                            @php $isSelected = $grid[$dayKey][$sessionKey] ?? false; @endphp
                            <td class="py-4 px-3 text-center">
                                <button type="button"
                                        @if($this->canEdit) wire:click="toggle('{{ $dayKey }}', '{{ $sessionKey }}')" @endif
                                        @class([
                                            'w-12 h-12 rounded-xl border-2 inline-flex items-center justify-center transition-all duration-150',
                                            'cursor-pointer active:scale-95' => $this->canEdit,
                                            'cursor-not-allowed' => !$this->canEdit,
                                            'bg-green-500 border-green-500 text-white shadow-md' => $isSelected,
                                            'bg-white border-gray-300 shadow-sm' => !$isSelected,
                                            'hover:bg-green-600 hover:border-green-600' => $isSelected && $this->canEdit,
                                            'hover:border-blue-500 hover:bg-blue-50' => !$isSelected && $this->canEdit,
                                            'opacity-60' => !$this->canEdit,
                                        ])>
                                    @if($isSelected)
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                </button>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Submit Button --}}
    @if($this->canEdit)
        <div class="flex justify-end">
            <button type="button" 
                    wire:click="submit" 
                    wire:loading.attr="disabled"
                    wire:confirm="Yakin ingin mengirim ketersediaan? Data tidak dapat diubah setelah dikirim."
                    @disabled($this->totalSelected === 0)
                    class="px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="submit">
                    Kirim Ketersediaan
                </span>
                <span wire:loading wire:target="submit" class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Mengirim...
                </span>
            </button>
        </div>
    @else
        <div class="bg-gray-50 rounded-xl p-4 text-center">
            <p class="text-sm text-gray-600">
                <svg class="w-5 h-5 inline mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Ketersediaan untuk minggu ini sudah dikirim dan tidak dapat diubah.
            </p>
        </div>
    @endif
</div>
