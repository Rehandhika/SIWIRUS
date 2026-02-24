<div class="space-y-6">
    {{-- Hero Section / Greeting --}}
    <div class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
        {{-- Background Pattern --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-16 -mt-16 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-black/10 rounded-full -ml-10 -mb-10 blur-2xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center border-2 border-white/30 shadow-inner">
                    <span class="text-2xl font-bold">{{ substr($this->user->name, 0, 1) }}</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Halo, {{ $this->user->name }}!</h1>
                    <p class="text-primary-100 text-sm mt-1 flex items-center gap-2">
                        <span class="opacity-75">{{ $this->user->nim }}</span>
                        <span class="w-1 h-1 bg-white rounded-full"></span>
                        <span class="font-medium bg-white/20 px-2 py-0.5 rounded text-xs">
                            {{ $this->user->roles->first()->name ?? 'Anggota' }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- Quick Stats & Clock --}}
            <div class="flex items-center gap-6 bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/10">
                <div class="text-center px-2">
                    <p class="text-xs text-primary-200 uppercase tracking-wider font-medium">Jam Sekarang</p>
                    <p class="text-xl font-bold font-mono" x-data x-init="setInterval(() => $el.innerText = new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}), 1000)">
                        {{ now()->format('H:i') }}
                    </p>
                </div>
                <div class="w-px h-8 bg-white/20"></div>
                <div class="text-center px-2">
                    <p class="text-xs font-bold text-emerald-300 italic max-w-[200px] leading-tight" 
                       x-data="{ 
                           quotes: [
                               'Many of life\'s failures are people who did not realize how close they were to success when they gave up. ~Thomas Edison',
                               'It does not matter how slowly you go as long as you do not stop. ~Confucius',
                               'It\'s fine to celebrate success but it is more important to heed the lessons of failure. ~Bill Gates',
                               'Start where you are. Use what you have. Do what you can. ~Arthur Ashe',
                               'Do what you can, with what you have, where you are. ~Theodore Roosevelt',
                               'If you cannot do great things, do small things in a great way. ~Napoleon Hill',
                               'The journey of a thousand miles begins with one step. ~Lao Tzu',
                               'Hard work never betrays results. ~Chairul Tanjung',
                               'Keep your spirit high, today is yours! ~Merry Riana',
                               'Opportunity knocks but once. ~Proverb',
                               'Be the best possible version of yourself. ~Oprah Winfrey',
                               'All our dreams can come true, if we have the courage to pursue them. ~Walt Disney',
                               'Success is a journey, not a destination. ~Ben Sweetland',
                               'Don\'t find fault, find a remedy. ~Henry Ford',
                               'Time is money. ~Benjamin Franklin',
                               'Discipline is the bridge between goals and accomplishment. ~Jim Rohn',
                               'Learn from yesterday, live for today, hope for tomorrow. ~Albert Einstein',
                               'Today must be better than yesterday. ~B.J. Habibie',
                               'Consistency beats intensity. ~Simon Sinek',
                               'Do not fear failure but rather fear not trying. ~Roy T. Bennett',
                               'Once you replace negative thoughts with positive ones, you\'ll start having positive results. ~Willie Nelson',
                               'You have to expect things of yourself before you can do them. ~Michael Jordan',
                               'Patience is a key element of success. ~Bill Gates',
                               'Do not spoil what you have by desiring what you have not. ~Epicurus',
                               'Life is a never-ending struggle. ~Soekarno',
                               'If you cannot stand the fatigue of study, you will feel the poignant of stupidity. ~Imam Syafi\'i',
                               'The future depends on what you do today. ~Mahatma Gandhi',
                               'Never leave that till tomorrow which you can do today. ~Benjamin Franklin',
                               'The most precious resource we all have is time. ~Steve Jobs',
                               'Commit yourself to lifelong learning. ~Brian Tracy',
                               'Challenges are opportunities to grow. ~John C. Maxwell',
                               'Believe you can and you\'re halfway there. ~Theodore Roosevelt',
                               'I think it is possible for ordinary people to choose to be extraordinary. ~Elon Musk',
                               'Setting goals is the first step in turning the invisible into the visible. ~Tony Robbins',
                               'The biggest risk is not taking any risk. ~Mark Zuckerberg',
                               'Success requires sacrifice. ~Tung Desem Waringin',
                               'I can accept failure, everyone fails at something. But I can\'t accept not trying. ~Michael Jordan',
                               'Success is a lousy teacher. It seduces smart people into thinking they can\'t lose. ~Bill Gates',
                               'Stay humble when successful. ~Bob Sadino',
                               'Carry out a random act of kindness, with no expectation of reward. ~Princess Diana',
                               'Respect the process, enjoy the result. ~Deddy Corbuzier',
                               'Don\'t compare yourself with anyone in this world. ~Bill Gates',
                               'In order to be irreplaceable one must always be different. ~Coco Chanel',
                               'Innovation distinguishes between a leader and a follower. ~Steve Jobs',
                               'Quality is more important than quantity. ~Steve Jobs',
                               'Simplicity is the ultimate sophistication. ~Leonardo da Vinci',
                               'Happiness depends upon ourselves. ~Aristotle',
                               'Smiling is charity. ~Hadith',
                               'Good morning, keep up the good work! ~Mario Teguh'
                           ],
                           currentQuote: ''
                       }" 
                       x-init="currentQuote = quotes[Math.floor(Math.random() * quotes.length)]; setInterval(() => currentQuote = quotes[Math.floor(Math.random() * quotes.length)], 10000)"
                       x-text="currentQuote"
                       x-transition:enter="transition ease-out duration-500"
                       x-transition:enter-start="opacity-0 transform scale-90"
                       x-transition:enter-end="opacity-100 transform scale-100">
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
        {{-- Left Column: Next Shift & Weekly Schedule --}}
        <div class="space-y-6">
            
            {{-- Next Shift Card --}}
            <x-ui.card class="border-l-4 border-l-primary-500">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-ui.icon name="calendar" class="w-5 h-5 text-primary-500" />
                            Shift Berikutnya
                        </h2>
                        @if($this->nextShift)
                            <div class="mt-2">
                                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                                    {{ \Carbon\Carbon::parse($this->nextShift->date)->isoFormat('dddd, D MMMM') }}
                                </p>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">
                                    Sesi {{ $this->nextShift->session }} • {{ $this->nextShift->schedule->name }}
                                </p>
                            </div>
                        @else
                            <p class="mt-2 text-gray-500 dark:text-gray-400">Belum ada jadwal shift mendatang.</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2 w-full sm:w-auto">
                        <x-ui.button href="{{ route('admin.attendance.check-in-out') }}" size="lg" class="w-full justify-center shadow-lg shadow-primary-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            Check In / Out
                        </x-ui.button>
                        <p class="text-xs text-center text-gray-400">Pastikan berada di lokasi toko</p>
                    </div>
                </div>
            </x-ui.card>

            {{-- Live Shift Monitor --}}
            <x-ui.card title="Live Shift Monitor">
                @if($this->activeShifts->count() > 0)
                    <div class="space-y-4">
                        @foreach($this->activeShifts as $assignment)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <x-ui.avatar :src="$assignment->user->photo_url" :name="$assignment->user->name" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                        {{ $assignment->user->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        @if($assignment->type === 'override')
                                            <span class="text-amber-600 font-medium">Override (Luar Jadwal)</span>
                                        @else
                                            Sesi {{ $assignment->session }} • {{ $assignment->schedule->name }}
                                        @endif
                                    </p>
                                </div>
                                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <x-ui.icon name="clock" class="w-8 h-8 mx-auto mb-2 text-gray-300" />
                        <p>Tidak ada shift aktif saat ini</p>
                    </div>
                @endif
            </x-ui.card>

            {{-- Full Weekly Schedule (Jadwal Minggu Ini Semua User) --}}
            <x-ui.card title="Jadwal Operasional Minggu Ini">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3 font-medium border-b dark:border-gray-700">Hari / Tanggal</th>
                                <th class="px-4 py-3 font-medium border-b dark:border-gray-700">Petugas Shift</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->fullWeeklySchedule as $date => $assignments)
                                @php
                                    $isToday = $date === now()->format('Y-m-d');
                                @endphp
                                <tr class="{{ $isToday ? 'bg-primary-50/50 dark:bg-primary-900/10' : 'bg-white dark:bg-gray-800' }}">
                                    <td class="px-4 py-3 align-top w-1/4">
                                        <div class="flex flex-col">
                                            <span class="font-bold {{ $isToday ? 'text-primary-600 dark:text-primary-400' : 'text-gray-900 dark:text-white' }}">
                                                {{ \Carbon\Carbon::parse($date)->isoFormat('dddd') }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($date)->isoFormat('D MMMM Y') }}
                                            </span>
                                            @if($isToday)
                                                <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 w-fit">
                                                    Hari Ini
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            @foreach($assignments as $assignment)
                                                <div class="flex items-center gap-3 p-2 rounded-lg border {{ $assignment->user_id === auth()->id() ? 'border-primary-200 bg-primary-50 dark:border-primary-800 dark:bg-primary-900/20' : 'border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/30' }}">
                                                    <x-ui.avatar :src="$assignment->user->photo_url" :name="$assignment->user->name" class="w-8 h-8" />
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium {{ $assignment->user_id === auth()->id() ? 'text-primary-900 dark:text-primary-100' : 'text-gray-900 dark:text-white' }} truncate">
                                                            {{ $assignment->user->name }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            Sesi {{ $assignment->session }} • {{ $assignment->schedule->name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            
                            @if($this->fullWeeklySchedule->isEmpty())
                                <tr>
                                    <td colspan="2" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Tidak ada jadwal operasional minggu ini.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Notifications (Bottom) --}}
            @if($this->userStats['notificationCount'] > 0)
                <x-ui.card class="bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-blue-900 dark:text-blue-100 flex items-center gap-2">
                            <x-ui.icon name="bell" class="w-5 h-5" />
                            Notifikasi Terbaru
                        </h3>
                        <span class="px-2 py-0.5 rounded-full bg-blue-200 text-blue-800 text-xs font-bold">
                            {{ $this->userStats['notificationCount'] }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        @foreach($this->userStats['notifications'] as $notif)
                            <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-blue-100 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $notif->title }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $notif->message }}</p>
                                <p class="text-[10px] text-gray-400 mt-2 text-right">{{ $notif->created_at->diffForHumans() }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 text-center">
                        <a href="{{ route('admin.notifications.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            Lihat Semua Notifikasi
                        </a>
                    </div>
                </x-ui.card>
            @endif


        </div>
    </div>
</div>
