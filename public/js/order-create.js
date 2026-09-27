const CSRF = document.querySelector('meta[name="csrf-token"]').content;

let allProducts = [];
let grandTotal  = 0;
let taxTotal    = 0;
let subtotal    = 0;
let lineIndex   = 0;

// ── Boot ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    fetchProducts();
    fetchLowStock();
    addProductLine();
    document.getElementById('customer_email').addEventListener('blur', lookupCustomer);
    document.getElementById('amount_given').addEventListener('input', updateBalance);
    document.getElementById('generate_btn').addEventListener('click', generateBill);
    document.getElementById('close_bill_btn').addEventListener('click', closeBill);
});

// ── Fetch all products ────────────────────────────────
function fetchProducts() {
    fetch('/api/products')
        .then(r => r.json())
        .then(data => {
            allProducts = data;
            renderAllSelects();
        });
}

// ── Fetch low stock ───────────────────────────────────
function fetchLowStock() {
    fetch('/api/products/low-stock')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('low_stock_list');
            if (data.length === 0) {
                list.innerHTML = '<li class="text-xs text-green-600">All products sufficiently stocked ✓</li>';
                return;
            }
            list.innerHTML = data.map(p =>
                `<li>• ${p.name} - ${p.stock} units left</li>`
            ).join('');
        });
}

// ── Build product select options ──────────────────────
function buildSelectOptions(selectedId = '') {
    return '<option value="">- Select product -</option>' +
        allProducts.map(p =>
            `<option value="${p.id}" ${p.id == selectedId ? 'selected' : ''}>
                ${p.name} (Stock: ${p.stock})
             </option>`
        ).join('');
}

function renderAllSelects() {
    document.querySelectorAll('.product-select').forEach(sel => {
        const current = sel.value;
        sel.innerHTML = buildSelectOptions(current);
    });
}

// ── Product lines ─────────────────────────────────────
function addProductLine() {
    const idx   = lineIndex++;
    const tbody = document.getElementById('product_lines');
    const tr    = document.createElement('tr');
    tr.id       = `line_${idx}`;
    tr.className = 'border-b border-gray-100';
    tr.innerHTML = `
        <td class="px-3 py-2">
            <select class="product-select w-full border border-gray-200 rounded px-2 py-1 text-sm"
                onchange="onProductChange(${idx})">
                ${buildSelectOptions()}
            </select>
        </td>
        <td class="px-3 py-2">
            <input type="number" id="qty_${idx}" value="1" min="1"
                class="w-full border border-gray-200 rounded px-2 py-1 text-center text-sm"
                oninput="onQuantityChange(${idx})" />
        </td>
        <td class="px-3 py-2 text-right text-gray-500 text-sm" id="price_${idx}">-</td>
        <td class="px-3 py-2 text-right font-medium text-sm"    id="total_${idx}">-</td>
        <td class="px-3 py-2 text-center">
            <button onclick="removeLine(${idx})"
                class="text-red-400 hover:text-red-600 text-xl font-bold leading-none">&times;</button>
        </td>`;
    tbody.appendChild(tr);
}

function removeLine(idx) {
    if (document.querySelectorAll('#product_lines tr').length === 1) return;
    document.getElementById(`line_${idx}`).remove();
    recalcTotals();
}

function getProductForRow(idx) {
    const sel = document.querySelector(`#line_${idx} .product-select`);
    if (!sel) return null;
    return allProducts.find(p => p.id == sel.value) || null;
}

function onProductChange(idx) {
    const p = getProductForRow(idx);
    document.getElementById(`price_${idx}`).textContent = p ? '₹' + parseFloat(p.price).toFixed(2) : '-';
    document.getElementById(`total_${idx}`).textContent = p ? '-' : '-';
    onQuantityChange(idx);
}

function onQuantityChange(idx) {
    const p   = getProductForRow(idx);
    const qty = parseInt(document.getElementById(`qty_${idx}`)?.value) || 0;
    if (p && qty > 0) {
        document.getElementById(`total_${idx}`).textContent = '₹' + (parseFloat(p.price) * qty).toFixed(2);
    } else {
        document.getElementById(`total_${idx}`).textContent = '-';
    }
    recalcTotals();
}

// ── Totals ────────────────────────────────────────────
function recalcTotals() {
    subtotal = 0;
    taxTotal = 0;

    document.querySelectorAll('#product_lines tr').forEach(tr => {
        const idx = tr.id.replace('line_', '');
        const p   = getProductForRow(idx);
        const qty = parseInt(document.getElementById(`qty_${idx}`)?.value) || 0;
        if (!p || qty <= 0) return;
        const lt  = parseFloat(p.price) * qty;
        subtotal += lt;
        taxTotal += lt * parseFloat(p.tax_percentage) / 100;
    });

    grandTotal = subtotal + taxTotal;

    document.getElementById('display_subtotal').textContent    = '₹' + subtotal.toFixed(2);
    document.getElementById('display_tax').textContent         = '₹' + taxTotal.toFixed(2);
    document.getElementById('display_grand_total').textContent = '₹' + grandTotal.toFixed(2);

    updateBalance();
}

// ── Balance & denominations ───────────────────────────
function updateBalance() {
    const given   = parseFloat(document.getElementById('amount_given').value) || 0;
    // Fix floating point precision by rounding to 2 decimals
    const balance = Math.round((given - grandTotal) * 100) / 100;

    document.getElementById('balance_section').classList.toggle('hidden', balance < 0 || given === 0);
    document.getElementById('short_section').classList.toggle('hidden',   balance >= 0 || given === 0);

    if (balance >= 0 && given > 0) {
        document.getElementById('display_balance').textContent      = '₹' + balance.toFixed(2);
        document.getElementById('display_denomination').textContent = denominationBreakdown(balance);
    } else if (given > 0) {
        document.getElementById('display_short').textContent = Math.abs(balance).toFixed(2);
    }
}

function denominationBreakdown(amount) {
    const denoms = [500, 200, 100, 50, 20, 10, 5, 2, 1];
    let rem = Math.round(amount);
    const parts = [];
    for (const d of denoms) {
        const count = Math.floor(rem / d);
        if (count > 0) { parts.push(`${count}×${d}`); rem -= count * d; }
    }
    return parts.join(' + ');
}

// ── Customer lookup ───────────────────────────────────
function lookupCustomer() {
    const email  = document.getElementById('customer_email').value.trim();
    const status = document.getElementById('customer_status');
    if (!email) return;

    fetch(`/api/customers/${encodeURIComponent(email)}/orders`)
        .then(r => {
            if (!r.ok) throw new Error('not found');
            return r.json();
        })
        .then(orders => {
            if (orders.length > 0) {
                document.getElementById('customer_name').value = orders[0]?.customer?.name ?? '';
                status.textContent = '✓ Existing customer found';
                status.className   = 'text-xs text-green-600';
            } else {
                status.textContent = '✦ New customer - will be created on order';
                status.className   = 'text-xs text-blue-500';
            }
            status.classList.remove('hidden');
        })
        .catch(() => {
            status.textContent = '✦ New customer - will be created on order';
            status.className   = 'text-xs text-blue-500';
            status.classList.remove('hidden');
        });
}

// ── Generate Bill ─────────────────────────────────────
function generateBill() {
    clearMessages();

    const email = document.getElementById('customer_email').value.trim();
    if (!email) { showError('Customer email is required.'); return; }

    const lines = [];
    document.querySelectorAll('#product_lines tr').forEach(tr => {
        const idx = tr.id.replace('line_', '');
        const sel = tr.querySelector('.product-select');
        const qty = parseInt(document.getElementById(`qty_${idx}`)?.value) || 0;
        if (sel?.value) lines.push({ product_id: parseInt(sel.value), quantity: qty });
    });

    if (lines.length === 0) { showError('Add at least one product.'); return; }

    const btn = document.getElementById('generate_btn');
    btn.disabled    = true;
    btn.textContent = 'Processing…';

    fetch('/api/orders', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': CSRF,
        },
        body: JSON.stringify({
            customer_email: email,
            customer_name:  document.getElementById('customer_name').value.trim(),
            products: lines,
        }),
    })
    .then(async r => {
        const data = await r.json();
        if (!r.ok) {
            const errors = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || 'Something went wrong.');
            showError(errors);
        } else {
            showBillModal(data);
            fetchLowStock();
            fetchProducts();
        }
    })
    .catch(() => showError('Request failed. Please try again.'))
    .finally(() => {
        btn.disabled    = false;
        btn.textContent = 'Generate Bill';
    });
}

// ── Bill Modal ────────────────────────────────────────
function showBillModal(order) {
    const given   = parseFloat(document.getElementById('amount_given').value) || 0;
    const balance = given - parseFloat(order.grand_total);

    const linesHtml = order.order_lines.map(l => `
        <tr class="border-t border-gray-50">
            <td class="py-1">${l.product.name}</td>
            <td class="py-1 text-center">${l.quantity}</td>
            <td class="py-1 text-right">₹${parseFloat(l.line_total).toFixed(2)}</td>
        </tr>`).join('');

    document.getElementById('bill_content').innerHTML = `
        <div class="flex justify-between text-gray-500">
            <span>Order ID</span><span class="font-mono font-bold">#${order.id}</span>
        </div>
        <div class="flex justify-between text-gray-500">
            <span>Customer</span><span>${order.customer.name}</span>
        </div>
        <table class="w-full text-xs border-t border-gray-100 mt-2">
            <thead>
                <tr class="text-gray-400 text-left">
                    <th class="py-1">Product</th>
                    <th class="py-1 text-center">Qty</th>
                    <th class="py-1 text-right">Total</th>
                </tr>
            </thead>
            <tbody>${linesHtml}</tbody>
        </table>
        <div class="border-t border-gray-100 pt-2 space-y-1 text-sm">
            <div class="flex justify-between text-gray-500"><span>Subtotal</span><span>₹${parseFloat(order.subtotal).toFixed(2)}</span></div>
            <div class="flex justify-between text-gray-500"><span>Tax</span><span>₹${parseFloat(order.tax_total).toFixed(2)}</span></div>
            <div class="flex justify-between font-bold text-base"><span>Grand Total</span><span>₹${parseFloat(order.grand_total).toFixed(2)}</span></div>
        </div>
        ${balance >= 0 ? `
        <div class="bg-green-50 rounded-lg p-3 text-center text-green-700 font-medium">
            Balance Returned: ₹${balance.toFixed(2)}
            <p class="text-xs font-normal mt-1">${denominationBreakdown(balance)}</p>
        </div>` : ''}`;

    document.getElementById('bill_modal').classList.remove('hidden');
}

function closeBill() {
    document.getElementById('bill_modal').classList.add('hidden');
    resetForm();
}

// ── Helpers ───────────────────────────────────────────
function showError(msg) {
    const el = document.getElementById('error_msg');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function clearMessages() {
    document.getElementById('error_msg').classList.add('hidden');
    document.getElementById('success_msg').classList.add('hidden');
}

function resetForm() {
    document.getElementById('customer_email').value = '';
    document.getElementById('customer_name').value  = '';
    document.getElementById('amount_given').value   = '';
    document.getElementById('customer_status').classList.add('hidden');
    document.getElementById('balance_section').classList.add('hidden');
    document.getElementById('short_section').classList.add('hidden');
    document.getElementById('product_lines').innerHTML = '';
    lineIndex = 0;
    subtotal = taxTotal = grandTotal = 0;
    recalcTotals();
    addProductLine();
    clearMessages();
}
