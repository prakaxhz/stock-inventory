@extends('layouts.app')

@section('content')

<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">Order History</h2>

    <div class="bg-white rounded-xl shadow p-6">
        <label class="block text-xs text-gray-500 mb-1">Search by Customer Email</label>
        <div class="flex flex-col sm:flex-row gap-3">
            <input type="email" id="search_email" placeholder="e.g. john@example.com"
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
            <button id="search_btn"
                class="bg-blue-600 text-white text-sm px-5 py-2 rounded-lg hover:bg-blue-700 transition">
                Search
            </button>
        </div>
        <p id="error_msg" class="text-red-500 text-sm mt-2 hidden"></p>
    </div>

    <div id="results" class="space-y-4 hidden"></div>

    <div id="empty_msg" class="hidden text-center text-gray-400 py-10">
        No orders found for this customer.
    </div>
</div>

<script src="{{ asset('js/order-history.js') }}"></script>
@endsection
