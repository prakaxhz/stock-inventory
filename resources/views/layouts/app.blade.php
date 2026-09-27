<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Store Billing</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold tracking-wide">Store Billing</h1>
        <nav class="flex gap-4 text-sm">
            <a href="{{ route('orders.create') }}" class="hover:underline">New Order</a>
            <a href="{{ route('orders.history') }}" class="hover:underline">Order History</a>
        </nav>
    </header>
    <main class="max-w-5xl mx-auto py-8 px-4">
        @yield('content')
    </main>
</body>
</html>
