@php
    $categories = \App\Http\Controllers\FinanceController::EXPENSE_CATEGORIES;
    $methods = \App\Http\Controllers\FinanceController::PAYMENT_METHODS;
    $types = \App\Http\Controllers\FinanceController::TRANSACTION_TYPES;
    $periodLabel = \App\Support\DateFilter::PERIODS[request('period')] ?? 'All time';
    $tabUrl = fn ($t) => route('finance.index', array_merge(request()->only('period', 'from', 'to'), ['tab' => $t]));
@endphp

<x-app-layout>
    <x-slot name="title">Finance &amp; Accounts</x-slot>

    <div class="space-y-6"
         x-data="{
            expenseOpen: {{ $errors->hasAny(['expense_date', 'category', 'amount', 'method', 'description']) ? 'true' : 'false' }},
            editing: @js(old('editing_id') ? (int) old('editing_id') : null),
            form: {
                expense_date: @js(old('expense_date', now()->format('Y-m-d'))),
                category: @js(old('category', 'other')),
                amount: @js(old('amount', '')),
                method: @js(old('method', 'cash')),
                description: @js(old('description', '')),
            },
            openNew() {
                this.editing = null;
                this.form = { expense_date: @js(now()->format('Y-m-d')), category: 'other', amount: '', method: 'cash', description: '' };
                this.expenseOpen = true;
            },
            openEdit(expense) { this.editing = expense.id; this.form = { ...expense }; this.expenseOpen = true; },
         }">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Finance &amp; Accounts</h2>
                <p class="text-sm text-gray-500">Money in and out, expenses, and who owes what.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($tab === 'overview')
                    <form method="GET" action="{{ route('finance.index') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="tab" value="overview">
                        <x-date-filter />
                        <x-btn type="submit" variant="secondary">Apply</x-btn>
                    </form>
                @endif
                <x-btn type="button" @click="openNew()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add Expense
                </x-btn>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">{{ session('status') }}</div>
        @endif

        <!-- Tabs -->
        <div class="border-b border-gray-200 flex gap-6 text-sm font-medium">
            @foreach(['overview' => 'Overview', 'transactions' => 'Transactions', 'expenses' => 'Expenses'] as $key => $label)
                <a href="{{ $tabUrl($key) }}"
                   class="-mb-px pb-3 border-b-2 transition-colors {{ $tab === $key ? 'border-jesco-600 text-jesco-600' : 'border-transparent text-gray-500 hover:text-gray-800' }}">{{ $label }}</a>
            @endforeach
        </div>

        @if($tab === 'overview')
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card label="Cash In" :counter="$summary['cash_in']" prefix="Rs. " :change="$periodLabel.' · payments received'" color="emerald">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                    </x-slot>
                </x-stat-card>
                <x-stat-card label="Cash Out" :counter="$summary['cash_out']" prefix="Rs. " :change="$periodLabel.' · supplier payments and expenses'" :positive="false" color="amber">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" /></svg>
                    </x-slot>
                </x-stat-card>
                <x-stat-card label="Net Cash Flow" :counter="$summary['net']" prefix="Rs. " :change="$summary['net'] >= 0 ? 'More came in than went out' : 'More went out than came in'" :positive="$summary['net'] >= 0" color="jesco">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                    </x-slot>
                </x-stat-card>
                <x-stat-card label="Expenses" :counter="$summary['expenses']" prefix="Rs. " :change="$periodLabel" :positive="false" color="rose">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                    </x-slot>
                </x-stat-card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <x-card class="p-5 lg:col-span-2">
                    <h3 class="font-semibold text-gray-800">Cash In vs Cash Out</h3>
                    <p class="text-xs text-gray-500 mb-4">Last 6 months</p>
                    <x-cash-flow-chart :months="$monthly" />
                </x-card>

                <x-card class="p-5">
                    <h3 class="font-semibold text-gray-800">Expenses by Category</h3>
                    <p class="text-xs text-gray-500 mb-4">{{ $periodLabel }}</p>
                    @php $topCategory = max(1, $byCategory->max('total')); @endphp
                    <div class="space-y-3">
                        @forelse($byCategory as $row)
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-700">{{ $row['label'] }}</span>
                                    <span class="font-medium text-gray-900">Rs. {{ number_format($row['total']) }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-gray-100">
                                    <div class="h-full rounded-full" style="width: {{ $row['total'] / $topCategory * 100 }}%; background: #2a78d6"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No expenses in this period.</p>
                        @endforelse
                    </div>
                </x-card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach([
                    ['title' => 'Receivable', 'hint' => 'Customers owe you', 'total' => $summary['receivable'], 'rows' => $receivables, 'route' => 'customers.show', 'all' => route('customers.index', ['balance' => 'due'])],
                    ['title' => 'Payable', 'hint' => 'You owe suppliers', 'total' => $summary['payable'], 'rows' => $payables, 'route' => 'suppliers.show', 'all' => route('suppliers.index', ['balance' => 'due'])],
                ] as $side)
                    <x-card>
                        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ $side['title'] }}</h3>
                                <p class="text-xs text-gray-500">{{ $side['hint'] }}</p>
                            </div>
                            <p class="text-lg font-semibold text-gray-900">Rs. {{ number_format($side['total']) }}</p>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @forelse($side['rows'] as $row)
                                <li class="px-5 py-3 flex items-center justify-between text-sm">
                                    <a href="{{ route($side['route'], $row['id']) }}" class="text-gray-800 hover:text-jesco-600">{{ $row['name'] }}</a>
                                    <span class="font-medium text-rose-600">Rs. {{ number_format($row['due']) }}</span>
                                </li>
                            @empty
                                <li class="px-5 py-6 text-center text-sm text-gray-500">Nothing outstanding.</li>
                            @endforelse
                        </ul>
                        <div class="px-5 py-3 border-t border-gray-100">
                            <a href="{{ $side['all'] }}" class="text-sm font-medium text-jesco-600 hover:underline">View all</a>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif

        @if($tab === 'transactions')
            <x-card>
                <form method="GET" action="{{ route('finance.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="transactions">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search party, reference or note..." class="w-full sm:w-64 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500" />
                    <x-filter-select name="type" placeholder="All types" :options="$types" />
                    <x-date-filter />
                    <x-btn type="submit" variant="secondary">Apply</x-btn>
                    @if(request()->anyFilled(['search', 'type', 'period']))
                        <a href="{{ route('finance.index', ['tab' => 'transactions']) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
                    @endif
                </form>
                <p class="px-5 py-2.5 text-xs text-gray-500 bg-gray-50 border-b border-gray-100">
                    Sales and purchases are bills. Money actually moves with payments and expenses.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Type</th>
                                <th class="px-5 py-3 font-medium">Reference</th>
                                <th class="px-5 py-3 font-medium">Party</th>
                                <th class="px-5 py-3 font-medium">Details</th>
                                <th class="px-5 py-3 font-medium text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($transactions as $t)
                                @php
                                    $isBill = in_array($t->type, ['sale', 'purchase']);
                                    $typeColor = match($t->type) {
                                        'payment_in' => 'green',
                                        'payment_out', 'expense' => 'red',
                                        default => 'gray',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $t->transaction_date->format('Y-m-d') }}</td>
                                    <td class="px-5 py-3"><x-badge :color="$typeColor">{{ $types[$t->type] ?? ucfirst($t->type) }}</x-badge></td>
                                    <td class="px-5 py-3 text-gray-600 whitespace-nowrap">
                                        @if($t->reference instanceof \App\Models\Order)
                                            <a href="{{ route('orders.show', $t->reference) }}" class="text-jesco-600 hover:underline">{{ $t->reference->order_number }}</a>
                                        @elseif($t->reference instanceof \App\Models\Purchase)
                                            <a href="{{ route('purchases.show', $t->reference) }}" class="text-jesco-600 hover:underline">{{ $t->reference->purchase_number }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-gray-800">
                                        @if($t->customer)
                                            <a href="{{ route('customers.show', $t->customer) }}" class="hover:text-jesco-600">{{ $t->customer->name }}</a>
                                        @elseif($t->supplier)
                                            <a href="{{ route('suppliers.show', $t->supplier) }}" class="hover:text-jesco-600">{{ $t->supplier->name }}</a>
                                        @elseif($t->type === 'expense')
                                            <span class="text-gray-500">{{ $categories[$t->category] ?? 'Expense' }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-gray-500">{{ $t->description }}</td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        @if($isBill)
                                            <span class="text-gray-700">Rs. {{ number_format(abs($t->amount)) }}</span>
                                            <p class="text-[11px] text-gray-400">billed</p>
                                        @else
                                            <span class="font-medium {{ $t->amount < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $t->amount < 0 ? '-' : '+' }}Rs. {{ number_format(abs($t->amount)) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-gray-500">No transactions match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">{{ $transactions->links() }}</div>
                @endif
            </x-card>
        @endif

        @if($tab === 'expenses')
            <x-card>
                <form method="GET" action="{{ route('finance.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="expenses">
                    <x-filter-select name="category" placeholder="All categories" :options="$categories" />
                    <x-date-filter />
                    <x-btn type="submit" variant="secondary">Apply</x-btn>
                    @if(request()->anyFilled(['category', 'period']))
                        <a href="{{ route('finance.index', ['tab' => 'expenses']) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
                    @endif
                    <p class="ml-auto text-sm text-gray-600">Total: <span class="font-semibold text-gray-900">Rs. {{ number_format($expenseTotal) }}</span></p>
                </form>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Category</th>
                                <th class="px-5 py-3 font-medium">Details</th>
                                <th class="px-5 py-3 font-medium">Paid Via</th>
                                <th class="px-5 py-3 font-medium text-right">Amount</th>
                                <th class="px-5 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($expenses as $e)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $e->transaction_date->format('Y-m-d') }}</td>
                                    <td class="px-5 py-3"><x-badge>{{ $categories[$e->category] ?? 'Other' }}</x-badge></td>
                                    <td class="px-5 py-3 text-gray-800">{{ $e->description }}</td>
                                    <td class="px-5 py-3 text-gray-500">{{ $methods[$e->payment_method] ?? 'Not recorded' }}</td>
                                    <td class="px-5 py-3 text-right font-medium text-gray-900 whitespace-nowrap">Rs. {{ number_format(abs($e->amount)) }}</td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-3">
                                            <button type="button" class="text-jesco-600 hover:underline text-sm font-medium"
                                                    @click="openEdit(@js(['id' => $e->id, 'expense_date' => $e->transaction_date->format('Y-m-d'), 'category' => $e->category ?? 'other', 'amount' => abs($e->amount), 'method' => $e->payment_method ?? 'cash', 'description' => $e->description]))">Edit</button>
                                            <button type="button" class="text-rose-600 hover:underline text-sm font-medium"
                                                    @click="$dispatch('confirm-delete', { url: '{{ route('finance.expenses.destroy', $e->id) }}', name: 'this expense of Rs. {{ number_format(abs($e->amount)) }}' })">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-gray-500">No expenses match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($expenses->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">{{ $expenses->links() }}</div>
                @endif
            </x-card>
        @endif

        <!-- Add / edit expense modal -->
        <div x-show="expenseOpen" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="expenseOpen = false">
            <div x-show="expenseOpen" x-transition.opacity @click="expenseOpen = false" class="fixed inset-0 bg-gray-900/50"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div x-show="expenseOpen" x-transition class="relative w-full max-w-md bg-white rounded-xl shadow-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900" x-text="editing ? 'Edit Expense' : 'Add Expense'"></h3>

                    <form method="POST" :action="editing ? '{{ url('finance/expenses') }}/' + editing : '{{ route('finance.expenses.store') }}'" class="mt-5 space-y-4">
                        @csrf
                        <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
                        <input type="hidden" name="editing_id" :value="editing ?? ''">

                        @if($errors->hasAny(['expense_date', 'category', 'amount', 'method', 'description']))
                            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm px-3 py-2">
                                @foreach($errors->only(['expense_date', 'category', 'amount', 'method', 'description']) as $message)
                                    <p>{{ $message }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="expense_date" class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
                                <input type="date" id="expense_date" name="expense_date" x-model="form.expense_date" required
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            </div>
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1.5">Amount (Rs.)</label>
                                <input type="number" id="amount" name="amount" x-model="form.amount" min="1" step="0.01" required
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            </div>
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                                <select id="category" name="category" x-model="form.category" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                                    @foreach($categories as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="method" class="block text-sm font-medium text-gray-700 mb-1.5">Paid Via</label>
                                <select id="method" name="method" x-model="form.method" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                                    @foreach($methods as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Details</label>
                            <input type="text" id="description" name="description" x-model="form.description" required placeholder="e.g. K-Electric bill, September"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                        </div>
                        <div class="pt-2 flex justify-end gap-2">
                            <x-btn type="button" variant="secondary" @click="expenseOpen = false">Cancel</x-btn>
                            <x-btn type="submit" x-text="editing ? 'Update Expense' : 'Save Expense'">Save Expense</x-btn>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <x-confirm-delete-modal title="Delete this expense?" />
    </div>
</x-app-layout>
