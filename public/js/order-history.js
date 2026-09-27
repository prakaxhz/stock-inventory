document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const email  = params.get('email');
    if (email) {
        document.getElementById('search_email').value = email;
        fetchOrders();
    }

    document.getElementById('search_email').addEventListener('keydown', e => {
        if (e.key === 'Enter') fetchOrders();
    });

    document.getElementById('search_btn').addEventListener('click', fetchOrders);
});

function fetchOrders() {
    const email   = document.getElementById('search_email').value.trim();
    const errorEl = document.getElementById('error_msg');
    const results = document.getElementById('results');
    const emptyEl = document.getElementById('empty_msg');

    errorEl.classList.add('hidden');
    results.classList.add('hidden');
    emptyEl.classList.add('hidden');
    results.innerHTML = '';

    if (!email) { showError('Please enter an email.'); return; }

    fetch(`/api/customers/${encodeURIComponent(email)}/orders`)
        .then(async r => {
            if (r.status === 404) throw new Error('No customer found with this email.');
            if (!r.ok)           throw new Error('Something went wrong.');
            return r.json();
        })
        .then(orders => {
            if (orders.length === 0) {
                emptyEl.classList.remove('hidden');
                return;
            }

            results.innerHTML = `<p class="text-sm text-gray-500">${orders.length} order(s) found for ${email}</p>`;

            orders.forEach(order => {
                const linesHtml = order.order_lines.map(line => `
                    <tr class="border-t border-gray-50">
                        <td class="py-1">${line.product.name}</td>
                        <td class="py-1 text-center">${line.quantity}</td>
                        <td class="py-1 text-right">₹${parseFloat(line.unit_price).toFixed(2)}</td>
                        <td class="py-1 text-right font-medium">₹${parseFloat(line.line_total).toFixed(2)}</td>
                    </tr>`).join('');

                const card = document.createElement('div');
                card.className = 'bg-white rounded-xl shadow p-5 space-y-3';
                card.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-gray-800">Order #${order.id}</p>
                            <p class="text-xs text-gray-400">${new Date(order.created_at).toLocaleString()}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-green-700">₹${parseFloat(order.grand_total).toFixed(2)}</p>
                            <p class="text-xs text-gray-400">Tax: ₹${parseFloat(order.tax_total).toFixed(2)}</p>
                        </div>
                    </div>
                    <table class="w-full text-sm text-gray-600">
                        <thead>
                            <tr class="text-xs text-gray-400 border-b border-gray-100">
                                <th class="py-1 text-left">Product</th>
                                <th class="py-1 text-center">Qty</th>
                                <th class="py-1 text-right">Unit Price</th>
                                <th class="py-1 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>${linesHtml}</tbody>
                    </table>`;

                results.appendChild(card);
            });

            results.classList.remove('hidden');
        })
        .catch(err => showError(err.message));
}

function showError(msg) {
    const el = document.getElementById('error_msg');
    el.textContent = msg;
    el.classList.remove('hidden');
}
