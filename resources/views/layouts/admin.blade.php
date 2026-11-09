<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Internal Memo PT TAG</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

    <aside class="w-60 bg-gray-900 text-gray-300 min-h-screen flex-shrink-0">
        <div class="px-5 py-4 text-white font-semibold border-b border-gray-800">
            Admin &middot; Internal Memo
        </div>
        <nav class="py-4 text-sm">
            @php
                $links = [
                    ['admin.memo-types.index', 'Jenis Memo'],
                    ['admin.approval-flows.index', 'Alur Approval'],
                    ['admin.roles.index', 'Role'],
                    ['admin.divisions.index', 'Divisi'],
                    ['admin.users.index', 'User / Karyawan'],
                ];
            @endphp
            @foreach ($links as [$route, $label])
                <a href="{{ route($route) }}"
                   class="block px-5 py-2.5 {{ request()->routeIs(str_replace('.index', '.*', $route)) ? 'bg-gray-800 text-white' : 'hover:bg-gray-800 hover:text-white' }}">
                    {{ $label }}
                </a>
            @endforeach
            <div class="border-t border-gray-800 mt-4 pt-4">
                <a href="{{ route('memos.index') }}" class="block px-5 py-2.5 hover:bg-gray-800 hover:text-white">
                    &larr; Kembali ke Aplikasi
                </a>
            </div>
        </nav>
    </aside>

    <div class="flex-1">
        <header class="bg-white border-b border-gray-200 h-14 flex items-center justify-between px-6">
            <h1 class="font-semibold text-gray-800">@yield('title', 'Dashboard Admin')</h1>
            <span class="text-sm text-gray-500">{{ auth()->user()->name }}</span>
        </header>

        <main class="p-6">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
