<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    <div class="space-y-6"
         x-data="{
            orders: [
                { id: 'ORD-1042', customer: 'Bilal Khan', company: 'Al-Madina Auto Workshop', date: '21 Sep 2026', total: 24500, status: 'Processing', statusColor: 'blue', items: [
                    { name: 'Jesco Motor Engine Oil 20W60 1L', qty: 10, price: 1150 },
                    { name: 'Jesco MP Gold Gear Oil 90W140 1L', qty: 12, price: 980 },
                ] },
                { id: 'ORD-1041', customer: 'Kamran Sheikh', company: 'Baldia Spare Parts & Lubricants', date: '21 Sep 2026', total: 5400, status: 'Delivered', statusColor: 'green', items: [
                    { name: 'Jesco Motorcycle Engine Oil 20W50 4T', qty: 6, price: 850 },
                ] },
                { id: 'ORD-1040', customer: 'Hassan Raza', company: 'Raza Automotive Garage', date: '20 Sep 2026', total: 98200, status: 'Pending', statusColor: 'yellow', items: [
                    { name: 'Jesco Motor Engine Oil CF-4/SJ 20W50 3L', qty: 20, price: 2950 },
                    { name: 'Jesco Motor Engine Oil SF/CF 20W60 3L', qty: 12, price: 3050 },
                ] },
                { id: 'ORD-1039', customer: 'Usman Ahmed', company: 'Quetta Terminal Motors', date: '19 Sep 2026', total: 15600, status: 'Delivered', statusColor: 'green', items: [
                    { name: 'Jesco ATF Dextron DX-III 1L', qty: 8, price: 1350 },
                    { name: 'Jesco MP Gold Gear Oil 90W140 1L', qty: 5, price: 980 },
                ] },
            ],
            selectedOrder: null,
            openOrder(order) {
                this.selectedOrder = order;
                $dispatch('open-modal', 'order-detail');
            },
         }">

        <!-- Stat cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total Sales (This Month)" :counter="1245000" prefix="Rs. " change="+12.4% vs last month" color="jesco">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.94" /></svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card label="Outstanding Balance" :counter="57500" prefix="Rs. " change="4 customers pending" :positive="false" color="rose">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card label="Low Stock Items" :counter="3" suffix=" Products" change="Needs reorder" :positive="false" color="amber">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card label="Total Orders" :counter="128" change="+8 new today" color="emerald">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.756 2.505-7.324a.75.75 0 00-.738-.894H5.106M7.5 14.25L5.106 5.11M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                </x-slot>
            </x-stat-card>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="salesDashboard()">
            <!-- Sales chart -->
            <x-card class="lg:col-span-2 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-800">Sales Overview</h2>
                    <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                        <button type="button" @click="setPeriod('7d')" :class="period === '7d' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'" class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors">7D</button>
                        <button type="button" @click="setPeriod('30d')" :class="period === '30d' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'" class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors">30D</button>
                        <button type="button" @click="setPeriod('12m')" :class="period === '12m' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'" class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors">12M</button>
                    </div>
                </div>
                <div class="h-64">
                    <canvas x-ref="salesCanvas"></canvas>
                </div>
            </x-card>

            <!-- Order status donut -->
            <x-card class="p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Order Status</h2>
                <div class="h-64">
                    <canvas x-ref="statusCanvas"></canvas>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Orders -->
            <x-card class="lg:col-span-2">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800">Recent Orders</h2>
                    @if(auth()->user()->canAccess('orders'))
                        <a href="{{ route('orders.index') }}" class="text-sm text-jesco-600 hover:underline">View all</a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium">Order</th>
                                <th class="px-5 py-3 font-medium">Customer</th>
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Total</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="order in orders" :key="order.id">
                                <tr @click="openOrder(order)" class="hover:bg-gray-50 cursor-pointer">
                                    <td class="px-5 py-3 font-medium text-gray-800" x-text="order.id"></td>
                                    <td class="px-5 py-3 text-gray-600" x-text="order.customer"></td>
                                    <td class="px-5 py-3 text-gray-500" x-text="order.date"></td>
                                    <td class="px-5 py-3 text-gray-800" x-text="'Rs. ' + order.total.toLocaleString('en-US')"></td>
                                    <td class="px-5 py-3">
                                        <span :class="{
                                            'bg-emerald-100 text-emerald-700': order.statusColor === 'green',
                                            'bg-sky-100 text-sky-700': order.statusColor === 'blue',
                                            'bg-amber-100 text-amber-700': order.statusColor === 'yellow',
                                            'bg-rose-100 text-rose-700': order.statusColor === 'red',
                                        }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" x-text="order.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </x-card>

            <!-- Low Stock Alerts -->
            <x-card>
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800">Low Stock Alerts</h2>
                    @if(auth()->user()->canAccess('inventory'))
                        <a href="{{ route('inventory.index') }}" class="text-sm text-jesco-600 hover:underline">View all</a>
                    @endif
                </div>
                <ul class="divide-y divide-gray-100">
                    <li class="px-5 py-3 flex items-center justify-between hover:bg-gray-50">
                        <div>
                            <p class="text-sm font-medium text-gray-800">Jesco Motor Engine Oil 20W60 1L</p>
                            <p class="text-xs text-gray-500">SKU: JSC-ME-1001</p>
                        </div>
                        <x-badge color="yellow">15 left</x-badge>
                    </li>
                    <li class="px-5 py-3 flex items-center justify-between hover:bg-gray-50">
                        <div>
                            <p class="text-sm font-medium text-gray-800">Jesco ATF Dextron DX-III 1L</p>
                            <p class="text-xs text-gray-500">SKU: JSC-ATF-1002</p>
                        </div>
                        <x-badge color="red">Out of stock</x-badge>
                    </li>
                    <li class="px-5 py-3 flex items-center justify-between hover:bg-gray-50">
                        <div>
                            <p class="text-sm font-medium text-gray-800">Jesco Motor Engine Oil 20W60 3L</p>
                            <p class="text-xs text-gray-500">SKU: JSC-ME-3005</p>
                        </div>
                        <x-badge color="yellow">8 left</x-badge>
                    </li>
                </ul>
            </x-card>
        </div>

        <!-- Order detail modal -->
        <x-modal name="order-detail" maxWidth="md">
            <template x-if="selectedOrder">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900" x-text="selectedOrder.id"></h3>
                            <p class="text-sm text-gray-500" x-text="selectedOrder.customer + ' · ' + selectedOrder.company"></p>
                        </div>
                        <span :class="{
                            'bg-emerald-100 text-emerald-700': selectedOrder.statusColor === 'green',
                            'bg-sky-100 text-sky-700': selectedOrder.statusColor === 'blue',
                            'bg-amber-100 text-amber-700': selectedOrder.statusColor === 'yellow',
                            'bg-rose-100 text-rose-700': selectedOrder.statusColor === 'red',
                        }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" x-text="selectedOrder.status"></span>
                    </div>

                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Items</p>
                        <ul class="space-y-2">
                            <template x-for="item in selectedOrder.items" :key="item.name">
                                <li class="flex items-center justify-between text-sm">
                                    <span class="text-gray-700" x-text="item.qty + '× ' + item.name"></span>
                                    <span class="text-gray-800 font-medium" x-text="'Rs. ' + (item.qty * item.price).toLocaleString('en-US')"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="mt-4 border-t border-gray-100 pt-4 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500">Order Total</span>
                        <span class="text-lg font-semibold text-gray-900" x-text="'Rs. ' + selectedOrder.total.toLocaleString('en-US')"></span>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <x-btn type="button" variant="secondary" @click="$dispatch('close-modal', 'order-detail')">Close</x-btn>
                        <x-btn type="button">View Invoice</x-btn>
                    </div>
                </div>
            </template>
        </x-modal>
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
    @endpush
</x-app-layout>
