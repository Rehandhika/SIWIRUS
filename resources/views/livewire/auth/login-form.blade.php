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
      <form wire:submit="login" class="px-8 pb-8 space-y-4">
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
            class="w-full"
          >
            <span wire:loading.remove>MASUK</span>
            <span wire:loading>Memproses...</span>
          </x-ui.button>
        </div>
      </form>
    </div>
</div>
