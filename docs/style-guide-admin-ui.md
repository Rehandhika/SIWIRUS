# Style Guide - Admin UI Components

Dokumen ini menjadi referensi konsistensi UI untuk halaman admin SIWIRUS menggunakan Laravel Livewire + Alpine.js + Tailwind CSS.

## Stack Teknologi
- **Backend**: Laravel 11 dengan Livewire 3
- **Frontend**: Alpine.js untuk interaktivitas, Tailwind CSS untuk styling
- **Component System**: Blade components dengan x-data untuk state management
- **Dark Mode**: Tailwind dark: prefix dengan semantic color tokens

## Prinsip Desain

### Konsistensi Visual
- Semua komponen menggunakan `rounded-lg` untuk border-radius (kecuali badge yang menggunakan `rounded-full`)
- Spacing konsisten menggunakan Tailwind spacing scale
- Transisi animasi konsisten (200-300ms duration)
- Focus ring untuk accessibility menggunakan `focus:ring-2 focus:ring-offset-2`

### Semantic Color Tokens
Gunakan semantic color tokens untuk memudahkan dark mode:
- **Primary**: Blue (blue-500/600/700)
- **Success**: Emerald (emerald-400/500/700)
- **Danger**: Red (red-400/500/700)
- **Warning**: Amber (amber-400/500/700)
- **Info**: Blue (blue-400/500/700)

### Dark Mode Support
Semua komponen mendukung dark mode dengan:
- Background: `dark:bg-gray-800`
- Text: `dark:text-gray-100/200/300`
- Border: `dark:border-gray-600/700`
- Variant colors disesuaikan untuk dark mode

---

## Komponen UI

### 1. Toast Notification

**Lokasi**: `resources/views/components/ui/toast.blade.php`

Toast notification untuk memberikan feedback kepada pengguna.

#### Props
```php
@props([
    'position' => 'top-right',  // top-right, top-left, bottom-right, bottom-left
    'duration' => 3000,         // Auto-dismiss duration in ms
    'maxToasts' => 5,           // Maximum visible toasts
])
```

#### Usage
```blade
{{-- Di layout utama (app.blade.php) --}}
<x-ui.toast />

{{-- Trigger dari Livewire --}}
$this->dispatch('toast', message: 'Data berhasil disimpan', type: 'success');

{{-- Trigger dari JavaScript --}}
window.dispatchEvent(new CustomEvent('toast', {
    detail: { message: 'Success!', type: 'success' }
}));
```

#### Variants
- `success`: Green dengan check-circle icon
- `error`: Red dengan x-circle icon
- `warning`: Yellow dengan exclamation-triangle icon
- `info`: Blue dengan information-circle icon

#### Features
- Auto-dismiss setelah duration yang ditentukan
- Manual dismiss dengan close button
- Stacking untuk multiple toasts
- Smooth enter/exit animations
- Maximum toast limit untuk mencegah overflow

---

### 2. Button

**Lokasi**: `resources/views/components/ui/button.blade.php`

Button component dengan berbagai variant dan size.

#### Props
```php
@props([
    'variant' => 'primary',     // primary, secondary, success, danger, warning, info, white, outline, ghost
    'size' => 'md',             // sm, md, lg
    'type' => 'button',         // button, submit, reset
    'loading' => false,         // Show loading spinner
    'disabled' => false,        // Disable button
    'icon' => null,             // Icon name (heroicons)
    'iconPosition' => 'left',   // left, right
    'href' => null,             // Render as link
])
```

#### Usage
```blade
{{-- Basic button --}}
<x-ui.button>Save</x-ui.button>

{{-- With variant and size --}}
<x-ui.button variant="success" size="lg">Submit</x-ui.button>

{{-- With icon --}}
<x-ui.button icon="plus" variant="primary">Add New</x-ui.button>

{{-- Loading state --}}
<x-ui.button :loading="true">Processing...</x-ui.button>

{{-- As link --}}
<x-ui.button href="/dashboard" variant="outline">Go to Dashboard</x-ui.button>

{{-- With Livewire --}}
<x-ui.button wire:click="save" :loading="$isSaving">Save</x-ui.button>
```

#### Variants
- `primary`: Blue background (default)
- `secondary`: Gray background
- `success`: Green background
- `danger`: Red background
- `warning`: Yellow background
- `info`: Blue background
- `white`: White background dengan border
- `outline`: Transparent dengan border blue
- `ghost`: Transparent tanpa border

#### Sizes
- `sm`: Small (px-3 py-1.5 text-xs)
- `md`: Medium (px-4 py-2 text-sm) - default
- `lg`: Large (px-6 py-3 text-base)

---

### 3. Input Field

**Lokasi**: `resources/views/components/ui/input.blade.php`

Input field dengan label, error state, dan icon support.

#### Props
```php
@props([
    'label' => null,
    'name' => '',
    'type' => 'text',           // text, email, password, number, tel, url, date, time, datetime-local
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,            // Error message
    'help' => null,             // Help text
    'icon' => null,             // Left icon
    'iconRight' => null,        // Right icon
    'prefix' => null,           // Text prefix (e.g., "Rp")
    'suffix' => null,           // Text suffix (e.g., "kg")
])
```

#### Usage
```blade
{{-- Basic input --}}
<x-ui.input 
    label="Email" 
    name="email" 
    type="email" 
    placeholder="user@example.com"
    required
/>

{{-- With icon --}}
<x-ui.input 
    label="Search" 
    name="search" 
    icon="magnifying-glass"
    placeholder="Search..."
/>

{{-- With error --}}
<x-ui.input 
    label="Username" 
    name="username" 
    :error="$errors->first('username')"
/>

{{-- With prefix/suffix --}}
<x-ui.input 
    label="Price" 
    name="price" 
    type="number" 
    prefix="Rp"
/>

{{-- With Livewire --}}
<x-ui.input 
    label="Name" 
    name="name" 
    wire:model.live="name"
/>
```

#### Features
- Label dengan required indicator (*)
- Error state dengan red border dan error message
- Help text untuk guidance
- Icon support (left dan right)
- Prefix/suffix text support
- Focus ring untuk accessibility
- Disabled state dengan gray background
- Dark mode support

---

### 4. Select

**Lokasi**: `resources/views/components/ui/select.blade.php`

Select dropdown dengan searchable option menggunakan Tom Select.

#### Props
```php
@props([
    'label' => null,
    'name' => '',
    'options' => [],            // ['value' => 'label'] or Collection
    'selected' => null,
    'placeholder' => 'Pilih...',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'help' => null,
    'searchable' => false,      // Enable search (uses Tom Select)
    'multiple' => false,        // Allow multiple selection
])
```

#### Usage
```blade
{{-- Basic select --}}
<x-ui.select 
    label="Status" 
    name="status" 
    :options="['active' => 'Active', 'inactive' => 'Inactive']"
    selected="active"
/>

{{-- Searchable select --}}
<x-ui.select 
    label="User" 
    name="user_id" 
    :options="$users"
    searchable
/>

{{-- Multiple select --}}
<x-ui.select 
    label="Tags" 
    name="tags[]" 
    :options="$tags"
    multiple
/>

{{-- With Livewire --}}
<x-ui.select 
    label="Category" 
    name="category_id" 
    :options="$categories"
    wire:model.live="category_id"
/>
```

---

### 5. Dropdown

**Lokasi**: `resources/views/components/ui/dropdown.blade.php`

Dropdown menu dengan configurable alignment dan width.

#### Props
```php
@props([
    'align' => 'right',         // left, right
    'width' => '48',            // 48, 56, 64
])
```

#### Usage
```blade
<x-ui.dropdown align="right" width="48">
    <x-slot name="trigger">
        <button class="flex items-center">
            <span>Options</span>
            <x-ui.icon name="chevron-down" class="ml-2 w-4 h-4" />
        </button>
    </x-slot>

    <x-ui.dropdown-item href="/profile">Profile</x-ui.dropdown-item>
    <x-ui.dropdown-item href="/settings">Settings</x-ui.dropdown-item>
    <x-ui.dropdown-item wire:click="logout">Logout</x-ui.dropdown-item>
</x-ui.dropdown>
```

#### Features
- Click outside to close
- Escape key to close
- Smooth animations
- Configurable alignment (left/right)
- Configurable width (48/56/64 units)
- Dark mode support

---

### 6. Modal

**Lokasi**: `resources/views/components/ui/modal.blade.php`

Modal dialog untuk interaksi fokus.

#### Props
```php
@props([
    'name' => 'modal',          // Unique identifier
    'title' => '',
    'subtitle' => null,         // Optional subtitle
    'maxWidth' => 'lg',         // sm, md, lg, xl, 2xl, 3xl, 4xl, full
    'closeable' => true,
    'closeOnEscape' => true,
    'closeOnClickOutside' => true,
    'persistent' => false,      // Prevent closing during action
])
```

#### Usage
```blade
{{-- Define modal --}}
<x-ui.modal name="create-user" title="Create New User" subtitle="Fill in the user details" maxWidth="lg">
    <form wire:submit="save">
        <x-ui.input label="Name" name="name" wire:model="name" />
        <x-ui.input label="Email" name="email" type="email" wire:model="email" />
        
        <x-slot name="footer">
            <x-ui.button variant="white" @click="$dispatch('close-modal-create-user')">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" :loading="$isSaving">
                Save
            </x-ui.button>
        </x-slot>
    </form>
</x-ui.modal>

{{-- Open modal --}}
<x-ui.button @click="$dispatch('open-modal-create-user')">
    Create User
</x-ui.button>

{{-- Close modal from Livewire --}}
$this->dispatch('close-modal-create-user');
```

#### Max Width Options
- `sm`: max-w-sm
- `md`: max-w-md
- `lg`: max-w-lg (default)
- `xl`: max-w-xl
- `2xl`: max-w-2xl
- `3xl`: max-w-3xl
- `4xl`: max-w-4xl
- `full`: max-w-full

#### Features
- Backdrop overlay dengan semi-transparent background
- Close dengan Escape key (jika closeable)
- Close dengan click backdrop (jika closeOnClickOutside)
- Persistent mode untuk mencegah close saat processing
- Body scroll prevention saat modal open
- Smooth enter/exit animations
- Optional header dengan title dan subtitle
- Optional footer untuk action buttons

---

### 7. Badge

**Lokasi**: `resources/views/components/ui/badge.blade.php`

Badge untuk status indicators dan labels.

#### Props
```php
@props([
    'variant' => 'default',     // default, success, danger, warning, info, primary
    'size' => 'md',             // sm, md
    'dot' => false,             // Show status dot
    'removable' => false,       // Show remove button
    'icon' => null,             // Optional icon
])
```

#### Usage
```blade
{{-- Basic badge --}}
<x-ui.badge>Default</x-ui.badge>

{{-- With variant --}}
<x-ui.badge variant="success">Active</x-ui.badge>
<x-ui.badge variant="danger">Inactive</x-ui.badge>
<x-ui.badge variant="warning">Pending</x-ui.badge>

{{-- With dot indicator --}}
<x-ui.badge variant="success" dot>Online</x-ui.badge>

{{-- Small size --}}
<x-ui.badge size="sm" variant="info">New</x-ui.badge>

{{-- With icon --}}
<x-ui.badge icon="check" variant="success">Verified</x-ui.badge>

{{-- Removable --}}
<x-ui.badge removable @remove="removeTag($id)">Tag Name</x-ui.badge>
```

#### Variants
- `default`: Gray
- `success`: Green
- `danger`: Red
- `warning`: Yellow
- `info`: Blue
- `primary`: Blue

#### Sizes
- `sm`: Small (text-xs px-2 py-0.5)
- `md`: Medium (text-sm px-2.5 py-1) - default

---

### 8. Alert

**Lokasi**: `resources/views/components/ui/alert.blade.php`

Alert untuk menampilkan pesan penting.

#### Props
```php
@props([
    'variant' => 'info',        // success, danger, warning, info
    'dismissible' => false,
    'icon' => true,             // Show variant icon
    'title' => null,            // Optional title
])
```

#### Usage
```blade
{{-- Basic alert --}}
<x-ui.alert variant="success">
    Data berhasil disimpan!
</x-ui.alert>

{{-- With title --}}
<x-ui.alert variant="warning" title="Perhatian">
    Pastikan data sudah benar sebelum menyimpan.
</x-ui.alert>

{{-- Dismissible --}}
<x-ui.alert variant="info" dismissible>
    Informasi penting untuk Anda.
</x-ui.alert>

{{-- Without icon --}}
<x-ui.alert variant="danger" :icon="false">
    Error occurred!
</x-ui.alert>
```

#### Variants
- `success`: Green dengan check-circle icon
- `danger`: Red dengan x-circle icon
- `warning`: Yellow dengan exclamation-triangle icon
- `info`: Blue dengan information-circle icon

#### Features
- Left border accent (border-l-4)
- Variant-specific icons
- Dismissible dengan close button
- Smooth exit animation saat dismiss
- Optional title
- Dark mode support

---

### 9. Card

**Lokasi**: `resources/views/components/ui/card.blade.php`

Card container untuk mengelompokkan konten.

#### Props
```php
@props([
    'padding' => true,          // Apply default padding
    'hover' => false,           // Enable hover effect
    'clickable' => false,       // Cursor pointer
    'bordered' => true,         // Show border
])
```

#### Usage
```blade
{{-- Basic card --}}
<x-ui.card>
    <h3 class="text-lg font-semibold">Card Title</h3>
    <p class="text-gray-600">Card content goes here.</p>
</x-ui.card>

{{-- Card with header and footer --}}
<x-ui.card>
    <x-slot name="header">
        <h3 class="text-lg font-semibold">User Information</h3>
    </x-slot>
    
    <p>Content here...</p>
    
    <x-slot name="footer">
        <x-ui.button>Save</x-ui.button>
    </x-slot>
</x-ui.card>

{{-- Clickable card with hover --}}
<x-ui.card hover clickable>
    <a href="/details" class="block">
        <h3>Click me</h3>
    </a>
</x-ui.card>

{{-- Card without padding --}}
<x-ui.card :padding="false">
    <img src="image.jpg" class="w-full" />
    <div class="p-4">
        <h3>Image Card</h3>
    </div>
</x-ui.card>
```

#### Features
- Consistent border-radius (rounded-xl)
- Optional padding (p-6)
- Optional hover effect
- Optional clickable cursor
- Optional header dan footer slots
- Dark mode support (white bg → gray-800)

---

### 10. Table

**Lokasi**: `resources/views/components/data/table.blade.php`

Table component untuk menampilkan data tabular.

#### Props
```php
@props([
    'striped' => false,         // Alternating row colors
    'hoverable' => true,        // Row hover effect
    'compact' => false,         // Reduced padding
    'responsive' => true,       // Horizontal scroll on mobile
])
```

#### Usage
```blade
<x-data.table striped hoverable>
    <x-slot name="header">
        <th>Name</th>
        <th>Email</th>
        <th>Status</th>
        <th>Actions</th>
    </x-slot>
    
    @foreach($users as $user)
    <tr>
        <td>{{ $user->name }}</td>
        <td>{{ $user->email }}</td>
        <td>
            <x-ui.badge :variant="$user->is_active ? 'success' : 'danger'">
                {{ $user->is_active ? 'Active' : 'Inactive' }}
            </x-ui.badge>
        </td>
        <td>
            <x-ui.button size="sm" variant="outline">Edit</x-ui.button>
        </td>
    </tr>
    @endforeach
    
    @if($users->isEmpty())
    <x-slot name="empty">
        <x-layout.empty-state 
            icon="users" 
            title="No users found"
            description="Start by adding your first user."
        />
    </x-slot>
    @endif
</x-data.table>
```

#### Features
- Consistent header styling (gray background, uppercase text)
- Row hover state
- Striped rows option
- Compact mode untuk reduced padding
- Responsive dengan horizontal scroll
- Empty state support
- Dark mode support

---

## Komponen Layout

### 11. Page Header

**Lokasi**: `resources/views/components/layout/page-header.blade.php`

Page header dengan title, subtitle, breadcrumbs, dan action buttons.

#### Props
```php
@props([
    'title' => '',
    'subtitle' => null,
    'backUrl' => null,          // Show back button
    'breadcrumbs' => [],        // Breadcrumb items
])
```

#### Usage
```blade
{{-- Basic page header --}}
<x-layout.page-header title="Users Management" />

{{-- With subtitle --}}
<x-layout.page-header 
    title="Users Management" 
    subtitle="Manage all users in the system"
/>

{{-- With back button --}}
<x-layout.page-header 
    title="Edit User" 
    backUrl="/users"
/>

{{-- With breadcrumbs --}}
<x-layout.page-header 
    title="User Details"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Users', 'url' => '/users'],
        ['label' => 'John Doe']
    ]"
/>

{{-- With action buttons --}}
<x-layout.page-header title="Users">
    <x-slot name="actions">
        <x-ui.button variant="outline" icon="download">Export</x-ui.button>
        <x-ui.button icon="plus">Add User</x-ui.button>
    </x-slot>
</x-layout.page-header>
```

#### Features
- Responsive layout (stacked on mobile, horizontal on desktop)
- Optional back button dengan arrow-left icon
- Optional breadcrumbs navigation
- Optional subtitle/description
- Action buttons slot aligned to right
- Consistent typography (text-2xl/3xl font-bold)
- Dark mode support

---

### 12. Filter Section

**Lokasi**: `resources/views/components/layout/filter-section.blade.php`

Filter section untuk list pages dengan search dan filter dropdowns.

#### Props
```php
@props([
    'searchPlaceholder' => 'Cari...',
    'searchModel' => 'search',  // Livewire model for search
    'showClear' => false,       // Show clear filters button
])
```

#### Usage
```blade
<x-layout.filter-section 
    searchPlaceholder="Search users..." 
    searchModel="search"
    :showClear="$hasFilters"
>
    {{-- Filter dropdowns --}}
    <x-ui.select 
        name="status" 
        :options="['all' => 'All Status', 'active' => 'Active', 'inactive' => 'Inactive']"
        wire:model.live="statusFilter"
    />
    
    <x-ui.select 
        name="role" 
        :options="$roles"
        wire:model.live="roleFilter"
    />
    
    {{-- Clear filters button (shown when showClear is true) --}}
    @if($hasFilters)
    <x-ui.button variant="outline" wire:click="clearFilters">
        Clear Filters
    </x-ui.button>
    @endif
</x-layout.filter-section>
```

#### Features
- Contained dalam Card component
- Search input dengan search icon
- Support multiple filter dropdowns
- Optional clear filters button
- Responsive layout (stacked on mobile)
- Dark mode support

---

### 13. Empty State

**Lokasi**: `resources/views/components/layout/empty-state.blade.php`

Empty state untuk menampilkan pesan saat tidak ada data.

#### Props
```php
@props([
    'icon' => 'inbox',          // Heroicon name
    'title' => 'Tidak ada data',
    'description' => null,
])
```

#### Usage
```blade
{{-- Basic empty state --}}
<x-layout.empty-state 
    icon="users" 
    title="No users found"
/>

{{-- With description --}}
<x-layout.empty-state 
    icon="document-text" 
    title="No documents"
    description="Upload your first document to get started."
/>

{{-- With action button --}}
<x-layout.empty-state 
    icon="shopping-cart" 
    title="Your cart is empty"
    description="Start adding products to your cart."
>
    <x-slot name="action">
        <x-ui.button href="/products" icon="plus">
            Browse Products
        </x-ui.button>
    </x-slot>
</x-layout.empty-state>
```

#### Features
- Centered layout dengan icon
- Primary message text
- Optional secondary/help text
- Optional action button slot
- Consistent spacing dan typography
- Dark mode support

---

### 14. Spinner (Loading State)

**Lokasi**: `resources/views/components/ui/spinner.blade.php`

Loading spinner untuk menampilkan loading state.

#### Props
```php
@props([
    'size' => 'md',             // sm, md, lg
    'color' => 'primary',       // primary, white, gray
    'overlay' => false,         // Show as overlay
])
```

#### Usage
```blade
{{-- Basic spinner --}}
<x-ui.spinner />

{{-- Different sizes --}}
<x-ui.spinner size="sm" />
<x-ui.spinner size="lg" />

{{-- Different colors --}}
<x-ui.spinner color="white" />
<x-ui.spinner color="gray" />

{{-- Overlay mode (blocks interaction) --}}
<x-ui.spinner overlay />

{{-- With Livewire wire:loading --}}
<div wire:loading>
    <x-ui.spinner overlay />
</div>
```

#### Sizes
- `sm`: 4x4 (w-4 h-4)
- `md`: 6x6 (w-6 h-6) - default
- `lg`: 8x8 (w-8 h-8)

#### Features
- Smooth rotation animation
- Multiple size options
- Multiple color options
- Overlay mode untuk blocking interactions
- Dark mode support

---

## Pola Penggunaan

### Form dengan Validation

```blade
<form wire:submit="save">
    <x-ui.card>
        <x-slot name="header">
            <h3 class="text-lg font-semibold">User Information</h3>
        </x-slot>
        
        <div class="space-y-4">
            <x-ui.input 
                label="Name" 
                name="name" 
                wire:model="name"
                :error="$errors->first('name')"
                required
            />
            
            <x-ui.input 
                label="Email" 
                name="email" 
                type="email"
                wire:model="email"
                :error="$errors->first('email')"
                required
            />
            
            <x-ui.select 
                label="Role" 
                name="role" 
                :options="$roles"
                wire:model="role"
                :error="$errors->first('role')"
                required
            />
        </div>
        
        <x-slot name="footer">
            <div class="flex justify-end space-x-3">
                <x-ui.button variant="white" href="/users">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" :loading="$isSaving">
                    Save User
                </x-ui.button>
            </div>
        </x-slot>
    </x-ui.card>
</form>
```

---

### List Page dengan Filter

```blade
<div>
    <x-layout.page-header title="Users Management">
        <x-slot name="actions">
            <x-ui.button icon="plus" wire:click="$dispatch('open-modal-create-user')">
                Add User
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>
    
    <x-layout.filter-section 
        searchPlaceholder="Search users..." 
        :showClear="$hasFilters"
    >
        <x-ui.select 
            name="status" 
            :options="['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']"
            wire:model.live="statusFilter"
        />
    </x-layout.filter-section>
    
    <x-ui.card :padding="false">
        <x-data.table striped hoverable>
            <x-slot name="header">
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </x-slot>
            
            @forelse($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <x-ui.badge :variant="$user->is_active ? 'success' : 'danger'">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </x-ui.badge>
                </td>
                <td>
                    <x-ui.dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="text-gray-400 hover:text-gray-600">
                                <x-ui.icon name="ellipsis-vertical" class="w-5 h-5" />
                            </button>
                        </x-slot>
                        
                        <x-ui.dropdown-item wire:click="edit({{ $user->id }})">
                            Edit
                        </x-ui.dropdown-item>
                        <x-ui.dropdown-item wire:click="delete({{ $user->id }})" class="text-red-600">
                            Delete
                        </x-ui.dropdown-item>
                    </x-ui.dropdown>
                </td>
            </tr>
            @empty
            <x-slot name="empty">
                <x-layout.empty-state 
                    icon="users" 
                    title="No users found"
                    description="Start by adding your first user."
                />
            </x-slot>
            @endforelse
        </x-data.table>
    </x-ui.card>
    
    {{-- Pagination --}}
    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
```

---

### Modal dengan Form

```blade
{{-- Modal definition --}}
<x-ui.modal name="create-user" title="Create New User" maxWidth="lg">
    <form wire:submit="save">
        <div class="space-y-4">
            <x-ui.input 
                label="Name" 
                name="name" 
                wire:model="form.name"
                :error="$errors->first('form.name')"
                required
            />
            
            <x-ui.input 
                label="Email" 
                name="email" 
                type="email"
                wire:model="form.email"
                :error="$errors->first('form.email')"
                required
            />
            
            <x-ui.select 
                label="Role" 
                name="role" 
                :options="$roles"
                wire:model="form.role"
                :error="$errors->first('form.role')"
                required
            />
        </div>
        
        <x-slot name="footer">
            <x-ui.button 
                variant="white" 
                @click="$dispatch('close-modal-create-user')"
            >
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" :loading="$isSaving">
                Save User
            </x-ui.button>
        </x-slot>
    </form>
</x-ui.modal>

{{-- Trigger button --}}
<x-ui.button @click="$dispatch('open-modal-create-user')" icon="plus">
    Add User
</x-ui.button>
```

---

### Confirmation Dialog

```blade
{{-- Simple confirmation dengan wire:confirm --}}
<x-ui.button 
    variant="danger" 
    wire:click="delete({{ $id }})"
    wire:confirm="Are you sure you want to delete this user?"
>
    Delete
</x-ui.button>

{{-- Complex confirmation dengan modal --}}
<x-ui.modal name="confirm-delete" title="Confirm Delete" maxWidth="md">
    <div class="space-y-4">
        <x-ui.alert variant="warning">
            This action cannot be undone. Are you sure you want to delete this user?
        </x-ui.alert>
        
        <p class="text-sm text-gray-600 dark:text-gray-400">
            User: <strong>{{ $userToDelete?->name }}</strong>
        </p>
    </div>
    
    <x-slot name="footer">
        <x-ui.button 
            variant="white" 
            @click="$dispatch('close-modal-confirm-delete')"
        >
            Cancel
        </x-ui.button>
        <x-ui.button 
            variant="danger" 
            wire:click="confirmDelete"
            :loading="$isDeleting"
        >
            Delete User
        </x-ui.button>
    </x-slot>
</x-ui.modal>
```

---

## Best Practices

### 1. Konsistensi Komponen
- Selalu gunakan komponen yang sudah ada, jangan buat custom styling
- Gunakan variant yang sesuai dengan konteks (success untuk positive action, danger untuk destructive action)
- Gunakan size yang konsisten dalam satu halaman

### 2. Accessibility
- Semua form field harus memiliki label
- Gunakan required indicator (*) untuk field wajib
- Gunakan error message yang jelas dan deskriptif
- Pastikan focus ring terlihat untuk keyboard navigation
- Gunakan semantic HTML (button untuk action, a untuk navigation)

### 3. Loading States
- Tampilkan loading spinner pada button saat processing
- Gunakan overlay spinner untuk blocking interactions
- Gunakan wire:loading untuk menampilkan loading state pada Livewire actions

### 4. Error Handling
- Tampilkan error message di bawah form field
- Gunakan toast notification untuk global errors
- Gunakan alert component untuk important warnings
- Gunakan red color (danger variant) untuk errors

### 5. Responsive Design
- Gunakan responsive classes (sm:, md:, lg:)
- Stack layout pada mobile (flex-col)
- Horizontal scroll untuk table pada mobile
- Reduce padding pada mobile (compact mode)

### 6. Dark Mode
- Semua komponen sudah support dark mode
- Gunakan semantic color tokens
- Test tampilan di light dan dark mode
- Pastikan contrast ratio memadai

### 7. Performance
- Gunakan wire:model.live hanya jika perlu real-time update
- Gunakan wire:model.blur untuk form fields yang tidak perlu real-time
- Lazy load images dan heavy components
- Minimize Alpine.js data untuk performance

---

## Komponen Tambahan

### Textarea

**Lokasi**: `resources/views/components/ui/textarea.blade.php`

```blade
<x-ui.textarea 
    label="Description" 
    name="description" 
    rows="4"
    wire:model="description"
    :error="$errors->first('description')"
/>
```

### Checkbox

**Lokasi**: `resources/views/components/ui/checkbox.blade.php`

```blade
<x-ui.checkbox 
    label="I agree to terms and conditions" 
    name="agree" 
    wire:model="agree"
/>
```

### Radio

**Lokasi**: `resources/views/components/ui/radio.blade.php`

```blade
<x-ui.radio 
    label="Option 1" 
    name="option" 
    value="1"
    wire:model="selectedOption"
/>
```

### Icon

**Lokasi**: `resources/views/components/ui/icon.blade.php`

```blade
<x-ui.icon name="check" class="w-5 h-5 text-green-500" />
```

Menggunakan Heroicons. Lihat [heroicons.com](https://heroicons.com) untuk daftar icon.

---

## Migrasi dari Komponen Lama

Jika Anda menemukan komponen yang belum menggunakan standar ini:

### 1. Update Button
```blade
{{-- Old --}}
<button class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>

{{-- New --}}
<x-ui.button variant="primary">Save</x-ui.button>
```

### 2. Update Input
```blade
{{-- Old --}}
<input type="text" name="name" class="border rounded px-3 py-2">

{{-- New --}}
<x-ui.input label="Name" name="name" />
```

### 3. Update Alert/Toast
```blade
{{-- Old --}}
<div class="bg-green-100 text-green-800 p-4">Success!</div>

{{-- New --}}
<x-ui.alert variant="success">Success!</x-ui.alert>

{{-- Or use toast for temporary messages --}}
$this->dispatch('toast', message: 'Success!', type: 'success');
```

### 4. Update Modal
```blade
{{-- Old --}}
<div x-show="showModal" class="fixed inset-0 z-50">
    <div class="bg-white p-6 rounded">
        <!-- content -->
    </div>
</div>

{{-- New --}}
<x-ui.modal name="my-modal" title="Modal Title">
    <!-- content -->
</x-ui.modal>
```

---

## Troubleshooting

### Toast tidak muncul
- Pastikan `<x-ui.toast />` sudah ada di layout utama (app.blade.php)
- Pastikan Alpine.js sudah loaded
- Check console untuk error JavaScript

### Select searchable tidak berfungsi
- Pastikan Tom Select library sudah loaded
- Pastikan attribute `data-tom-select` ada pada select element
- Check tom-select-config.js sudah di-import

### Modal tidak close
- Pastikan event name sesuai: `open-modal-{name}` dan `close-modal-{name}`
- Pastikan Alpine.js sudah loaded
- Check console untuk error

### Dark mode tidak berfungsi
- Pastikan class `dark` ada di `<html>` element
- Pastikan semua komponen menggunakan `dark:` prefix
- Check Tailwind config untuk dark mode configuration

### Styling tidak sesuai
- Clear browser cache
- Run `npm run build` untuk production
- Check apakah ada custom CSS yang override
- Pastikan Tailwind classes tidak di-purge

---

## Referensi

### Dokumentasi
- [Laravel Livewire](https://livewire.laravel.com)
- [Alpine.js](https://alpinejs.dev)
- [Tailwind CSS](https://tailwindcss.com)
- [Heroicons](https://heroicons.com)

### File Penting
- Layout: `resources/views/layouts/app.blade.php`
- Components: `resources/views/components/`
- Tailwind Config: `tailwind.config.js`
- Alpine Config: `resources/js/alpine-init.js`

### Kontak
Jika ada pertanyaan atau menemukan bug, silakan hubungi tim development.

---

**Last Updated**: January 2026
**Version**: 1.0.0

