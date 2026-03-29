<div class="w-[90%] sm:w-[60%] md:w-[45%] lg:w-[35%] xl:w-[28%]">
    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      <!-- Header -->
      <div class="px-8 pt-10 pb-6 text-center">
        <!-- Logo -->
        <div class="mb-4 flex justify-center">
          <img src="{{ asset('images/logo.png') }}" alt="SIWIRUS" class="w-24 h-auto">
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-1">Login Pengurus</h1>
      </div>

      <!-- Form -->
      <form wire:submit.prevent="login" method="POST" class="px-8 pb-8 space-y-4">
        @csrf
        <!-- Username -->
        <x-ui.input
          label="Username"
          name="nim"
          type="text"
          placeholder="Masukkan NIM Anda"
          wire:model="nim"
          :error="$errors->first('nim')"
          autofocus
        />

        <!-- Password -->
        <x-ui.input
          label="Password"
          name="password"
          type="password"
          placeholder="Masukkan password Anda"
          wire:model="password"
          :error="$errors->first('password')"
        />



        <!-- Submit -->
        <div class="flex justify-center mt-6">
          <x-ui.button
            type="submit"
            variant="primary"
            wire:loading.attr="disabled"
            wire:loading.delay.300ms
            class="w-full transition-opacity duration-300"
          >
            <span wire:loading.remove>MASUK</span>
            <span wire:loading class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.835 5.728 4.571 7.271l1.429-1.98z"></path>
              </svg>
              Memproses...
            </span>
          </x-ui.button>
        </div>
      </form>
    </div>
</div>
