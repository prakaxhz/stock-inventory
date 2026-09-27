@extends('layouts.app')

@section('content')

<div class="space-y-6">
    
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">New Order</h2>
        <span class="text-xs text-gray-400 uppercase tracking-widest">Store Billing</span>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <div class="flex-1 space-y-6">

            <div class="bg-white rounded-xl shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-700">Customer</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Email</label>
                        <input type="email" id="customer_email" placeholder="e.g. thomas@example.com"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Name</label>
                        <input type="text" id="customer_name" placeholder="Auto-filled if email exists"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                    </div>
                </div>
                <p id="customer_status" class="text-xs hidden"></p>
            </div>

            <div class="bg-white rounded-xl shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-700">Products</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[500px]">
                        <thead>
                            <tr class="bg-gray-100 text-left text-gray-600 text-xs uppercase tracking-wide">
                                <th class="px-3 py-2 rounded-l">Product</th>
                                <th class="px-3 py-2 w-20 text-center">Qty</th>
                                <th class="px-3 py-2 w-28 text-right">Price</th>
                                <th class="px-3 py-2 w-28 text-right">Line Total</th>
                                <th class="px-3 py-2 w-10 rounded-r"></th>
                            </tr>
                        </thead>
                        <tbody id="product_lines"></tbody>
                    </table>
                </div>
                <button onclick="addProductLine()"
                    class="mt-2 bg-blue-600 text-white text-sm px-5 py-2 rounded-lg hover:bg-blue-700 transition">
                    + Add Product
                </button>
            </div>

            <div class="bg-white rounded-xl shadow p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-700">Payment</h3>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Subtotal</span>
                        <span id="display_subtotal" class="font-medium">₹0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tax</span>
                        <span id="display_tax" class="font-medium">₹0.00</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold">
                        <span>Grand Total</span>
                        <span id="display_grand_total">₹0.00</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Amount Given by Customer</label>
                    <input type="number" id="amount_given" placeholder="₹0.00"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                </div>

                <div id="balance_section" class="hidden bg-green-50 border border-green-200 rounded-lg p-3 text-sm">
                    <div class="flex justify-between font-semibold text-green-800">
                        <span>Balance to Return</span>
                        <span id="display_balance">₹0.00</span>
                    </div>
                    <p id="display_denomination" class="text-xs text-green-600 mt-1"></p>
                </div>

                <div id="short_section" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600">
                    Short by ₹<span id="display_short">0.00</span>
                </div>

                <div id="error_msg"   class="hidden bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm"></div>
                <div id="success_msg" class="hidden bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 text-sm"></div>

                <button id="generate_btn"
                    class="w-full bg-green-600 text-white font-semibold py-3 rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Generate Bill
                </button>
                <p class="text-xs text-gray-400 text-center">→ Shows bill on page + emails confirmation to customer</p>
            </div>
        </div>

        <div class="w-full lg:w-64 shrink-0">
            <div class="bg-amber-50 border border-amber-300 rounded-xl p-4 sticky top-6">
                <h4 class="text-sm font-bold text-amber-700 mb-3">⚠ Low Stock Alert</h4>
                <ul id="low_stock_list" class="space-y-1 text-sm text-amber-800">
                    <li class="text-xs text-gray-400">Loading...</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div id="bill_modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">🧾 Order Confirmed</h3>
            <button id="close_bill_btn" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <div id="bill_content" class="space-y-3 text-sm"></div>
    </div>
</div>

<script src="{{ asset('js/order-create.js') }}"></script>
@endsection
