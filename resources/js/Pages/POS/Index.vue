<template>
  <PosLayout :session="session">
    <div class="flex-1 flex flex-col xl:flex-row min-h-0 min-w-0">
      <!-- ═══ CATALOG ═══ -->
      <section class="flex-1 flex flex-col min-w-0 min-h-0">
        <div class="px-4 pt-4 pb-3 shrink-0 space-y-3">
          <div class="max-w-[1200px] mx-auto w-full space-y-3">
            <div class="pro-search !py-3 shadow-sm">
              <span class="text-text-tertiary shrink-0"><MagnifyingGlassIcon class="w-5 h-5" /></span>
              <input v-model="searchQuery" @keydown.enter="searchExactMatch" type="text" :placeholder="$t('page.pos.search_scan')" ref="searchInput">
              <button v-if="searchQuery" @click="searchQuery = ''" class="flex items-center justify-center w-7 h-7 rounded-lg text-text-tertiary hover:text-text-primary hover:bg-surface-tertiary shrink-0 transition" title="Effacer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
              <kbd class="hidden lg:inline-flex items-center px-1.5 py-0.5 rounded-md bg-surface border border-border text-[10px] font-bold text-text-tertiary shrink-0">SCAN</kbd>
            </div>
            <div class="flex items-center gap-2">
              <div v-if="categories.length" class="flex items-center gap-2 overflow-x-auto custom-scrollbar flex-1 pb-0.5">
                <button
                  @click="activeCategory = ''"
                  class="px-4 py-2 rounded-2xl text-[13px] font-extrabold whitespace-nowrap border transition-all active:scale-95"
                  :class="!activeCategory ? 'bg-neutral-900 text-white border-neutral-900 dark:bg-white dark:text-neutral-900 dark:border-white shadow-md' : 'bg-surface text-text-secondary border-border hover:border-emerald-400/50 hover:text-text-primary'"
                >{{ $t('common.all') }}</button>
                <button
                  v-for="c in categories" :key="c"
                  @click="activeCategory = activeCategory === c ? '' : c"
                  class="px-4 py-2 rounded-2xl text-[13px] font-extrabold whitespace-nowrap border transition-all active:scale-95"
                  :class="activeCategory === c ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/25' : 'bg-surface text-text-secondary border-border hover:border-emerald-400/50 hover:text-text-primary'"
                >{{ c }}</button>
              </div>
              <span class="text-xs font-bold text-text-tertiary tabular-nums whitespace-nowrap shrink-0 ml-auto">{{ filteredProducts.length }} / {{ products.length }}</span>
            </div>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 pb-6 custom-scrollbar min-h-[180px]">
          <div class="max-w-[1200px] mx-auto">
            <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 2xl:grid-cols-5 gap-4">
              <div v-for="i in 10" :key="i" class="rounded-3xl bg-surface border border-border/60 overflow-hidden">
                <div class="h-32 pro-skeleton !rounded-none" />
                <div class="p-3 space-y-2"><div class="h-3.5 w-3/4 pro-skeleton" /><div class="h-3 w-1/2 pro-skeleton" /></div>
              </div>
            </div>
            <div v-else-if="filteredProducts.length === 0" class="flex flex-col justify-center items-center text-center py-16">
              <span class="pro-empty-icon"><MagnifyingGlassIcon class="w-7 h-7" /></span>
              <p class="text-sm font-extrabold text-text-primary">{{ $t('page.pos.no_products') }}</p>
              <p class="text-xs font-medium text-text-tertiary mt-1">{{ $t('page.pos.cart_hint') }}</p>
              <button v-if="searchQuery || activeCategory" @click="searchQuery = ''; activeCategory = ''" class="mt-4 px-4 py-2.5 text-[13px] font-extrabold rounded-2xl border border-border bg-surface hover:border-emerald-400/50 shadow-sm active:scale-95 transition">{{ $t('common.clear_filters') }}</button>
            </div>
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 2xl:grid-cols-5 gap-4 auto-rows-max">
              <button
                v-for="product in filteredProducts" :key="product.id" @click="addToCart(product)"
                class="group relative text-left bg-surface rounded-3xl shadow-sm border border-border/60 overflow-hidden transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/15 hover:border-emerald-400/70 active:scale-[0.97]"
              >
                <div class="h-32 bg-surface-tertiary flex items-center justify-center relative overflow-hidden">
                  <img v-if="product.image" :src="product.image.startsWith('http') ? product.image : '/storage/' + product.image"
                    class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-110" :alt="product.name">
                  <span v-else class="text-lg font-black text-text-tertiary/70 transition-transform duration-500 group-hover:scale-110">{{ (product.name || '?').charAt(0).toUpperCase() }}</span>
                  <div class="absolute inset-0 bg-gradient-to-t from-black/25 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none" />
                  <span v-if="stockOf(product) !== null" class="absolute top-2 left-2 px-2 py-1 rounded-lg text-[10px] font-extrabold tabular-nums backdrop-blur border"
                    :class="stockOf(product) > 0 ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50'">
                    {{ stockOf(product) > 0 ? $t('page.pos.stock_left', { n: stockOf(product) }) : $t('status.out_of_stock') }}
                  </span>
                  <span v-if="product.price_xof !== undefined" class="absolute bottom-2 right-2 bg-gradient-to-br from-emerald-500 to-teal-600 text-white text-xs font-extrabold tabular-nums px-2.5 py-1 rounded-xl shadow-md shadow-emerald-600/30">{{ formatMoney(product.price_xof) }}</span>
                  <span class="absolute bottom-2 left-2 inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-neutral-950/70 backdrop-blur text-white text-[11px] font-extrabold opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ $t('page.pos.add_btn') }}
                  </span>
                </div>
                <div class="p-3">
                  <h3 class="font-extrabold text-text-primary text-[13px] line-clamp-1 leading-tight transition-colors group-hover:text-emerald-600">{{ product.name }}</h3>
                  <p class="mt-0.5 text-[11px] font-semibold text-text-tertiary font-mono truncate">{{ product.sku || product.barcode || '—' }}</p>
                </div>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ CART ═══ -->
      <aside class="shrink-0 w-full xl:w-[400px] border-t xl:border-t-0 xl:border-l border-border/70 bg-surface flex flex-col min-h-0 max-h-[48vh] xl:max-h-none z-20 shadow-[0_-12px_36px_-16px_rgb(0_0_0/0.2)]">
        <div class="px-4 pt-3 shrink-0">
          <button @click="openCustomerPicker" class="w-full flex items-center gap-3 text-left rounded-2xl border border-border/70 bg-surface-secondary/60 hover:border-emerald-400/50 hover:bg-surface-secondary px-3.5 py-2.5 transition-all group">
            <span class="flex items-center justify-center w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/15 shrink-0 font-black">
              {{ selectedCustomer ? (selectedCustomer.name || '?').charAt(0).toUpperCase() : '' }}
              <UserIcon v-if="!selectedCustomer" class="w-5 h-5" />
            </span>
            <span class="flex-1 min-w-0">
              <span class="block text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('page.pos.customer') }}</span>
              <span class="block font-extrabold text-text-primary text-sm truncate">{{ selectedCustomer?.name || $t('page.pos.default_customer') }}</span>
            </span>
            <ChevronDownIcon class="w-4 h-4 text-text-tertiary group-hover:text-emerald-500 transition-colors shrink-0" />
          </button>
        </div>

        <div class="flex items-center justify-between px-4 pt-3 pb-1 shrink-0">
          <h2 class="text-sm font-extrabold tracking-tight text-text-primary flex items-center gap-2">
            {{ $t('page.pos.cart_title') }}
            <span v-if="cartCount > 0" class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full bg-emerald-500 text-white text-[11px] font-black tabular-nums">{{ cartCount }}</span>
          </h2>
          <button v-if="cart.length" @click="clearCart" class="inline-flex items-center gap-1 text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary hover:text-rose-500 transition-colors">
            <TrashIcon class="w-3.5 h-3.5" /> {{ $t('page.pos.clear_cart') }}
          </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-2 custom-scrollbar min-h-0">
          <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center text-center py-8">
            <span class="flex items-center justify-center w-14 h-14 rounded-3xl bg-surface-secondary border border-border text-text-tertiary mb-3"><ShoppingCartIcon class="w-7 h-7" /></span>
            <p class="text-sm font-extrabold text-text-primary">{{ $t('page.pos.cart_empty') }}</p>
            <p class="text-xs font-medium text-text-tertiary mt-1 max-w-[220px]">{{ $t('page.pos.cart_hint') }}</p>
          </div>
          <TransitionGroup v-else name="cart" tag="div" class="space-y-2">
            <div v-for="(item, index) in cart" :key="item.product_id" class="bg-surface-secondary/50 border border-border/60 hover:border-emerald-400/40 p-2.5 pl-3 rounded-2xl flex items-center gap-2.5 transition-colors">
              <div class="flex-1 min-w-0">
                <h4 class="font-extrabold text-text-primary truncate text-[13px]">{{ item.name }}</h4>
                <div class="text-emerald-600 font-extrabold tabular-nums text-[13px] mt-0.5">{{ formatMoney(item.price_xof) }} <span class="text-text-tertiary font-semibold">× {{ item.quantity }}</span></div>
              </div>
              <div class="flex items-center bg-surface border border-border/70 rounded-xl p-0.5 gap-0.5 shrink-0">
                <button @click="updateQty(index, -1)" class="w-7 h-7 flex items-center justify-center text-text-secondary hover:bg-surface-tertiary rounded-lg font-black active:scale-90 transition">−</button>
                <span class="w-7 text-center font-black tabular-nums text-text-primary text-[13px]">{{ item.quantity }}</span>
                <button @click="updateQty(index, 1)" class="w-7 h-7 flex items-center justify-center text-emerald-600 hover:bg-emerald-500/10 rounded-lg font-black active:scale-90 transition">+</button>
              </div>
              <div class="text-right shrink-0 w-[74px]">
                <p class="text-[13px] font-black tabular-nums text-text-primary">{{ formatMoney(item.price_xof * item.quantity) }}</p>
                <button @click="removeFromCart(index)" class="text-[10px] font-bold text-text-tertiary hover:text-rose-500 transition-colors">✕</button>
              </div>
            </div>
          </TransitionGroup>
        </div>

        <div class="border-t border-border/70 p-4 space-y-3 bg-surface shrink-0">
          <div class="flex justify-between items-end">
            <div>
              <p class="text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('page.pos.total_due') }}</p>
              <p class="text-xs font-semibold text-text-tertiary tabular-nums">{{ $t('page.pos.items_label', { n: cartCount }) }}</p>
            </div>
            <span class="text-[32px] leading-none font-black text-emerald-600 tracking-tight tabular-nums">{{ formatMoney(cartTotal) }}</span>
          </div>

          <div class="grid grid-cols-3 gap-2">
            <button @click="openPaymentModal('cash')" :disabled="cart.length === 0 || paying"
              class="py-3 rounded-2xl font-extrabold text-[13px] flex items-center justify-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-[0.97]"
              :class="cart.length ? 'bg-neutral-900 text-white hover:bg-neutral-700 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 shadow-lg' : 'bg-surface-tertiary text-text-tertiary'">
              <BanknotesIcon class="w-5 h-5" />{{ $t('page.pos.pay_cash') }}
            </button>
            <button @click="openPaymentModal('card')" :disabled="cart.length === 0 || paying"
              class="py-3 rounded-2xl font-extrabold text-[13px] flex items-center justify-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-[0.97]"
              :class="cart.length ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-600/25' : 'bg-surface-tertiary text-text-tertiary'">
              <CreditCardIcon class="w-5 h-5" />{{ $t('page.pos.pay_card') }}
            </button>
            <button @click="openPaymentModal('mobile_money')" :disabled="cart.length === 0 || paying"
              class="py-3 rounded-2xl font-extrabold text-[13px] flex items-center justify-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-[0.97]"
              :class="cart.length ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white hover:brightness-110 shadow-lg shadow-emerald-600/25' : 'bg-surface-tertiary text-text-tertiary'">
              <DevicePhoneMobileIcon class="w-5 h-5" />M-Money
            </button>
          </div>

          <button @click="openCloseModal" :disabled="closing"
            class="w-full py-2.5 text-rose-600 hover:text-white bg-transparent hover:bg-rose-500 font-extrabold rounded-2xl text-[13px] transition-all border border-dashed border-rose-300 hover:border-rose-500 disabled:opacity-40 active:scale-[0.99]">
            {{ $t('page.pos.close_register') }}
          </button>
        </div>
      </aside>
    </div>

    <!-- Payment Modal -->
    <BaseModal :model-value="showPaymentModal" @update:model-value="showPaymentModal = $event" :title="$t('page.pos.pay_with', { method: paymentLabel(paymentMethod) })" :subtitle="$t('page.pos.amount_to_collect')" size="md">
      <div class="space-y-4">
        <p class="text-center text-5xl font-black text-emerald-600 tracking-tight tabular-nums">{{ formatMoney(cartTotal) }}</p>
        <p v-if="selectedCustomer" class="text-center text-sm text-text-tertiary -mt-2">{{ $t('page.pos.client_label') }} : <strong class="text-text-primary">{{ selectedCustomer.name }}</strong></p>
        <div class="grid grid-cols-3 gap-2 p-1 rounded-2xl bg-surface-tertiary">
          <button v-for="m in ['cash', 'card', 'mobile_money']" :key="m" @click="paymentMethod = m; amountReceived = cartTotal"
            class="py-2.5 rounded-xl text-[13px] font-extrabold transition-all active:scale-95"
            :class="paymentMethod === m ? 'bg-surface text-text-primary shadow-md border border-border' : 'text-text-tertiary hover:text-text-secondary'">
            {{ paymentLabel(m) }}
          </button>
        </div>
        <div v-if="paymentMethod === 'cash'" class="space-y-3">
          <div>
            <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1.5">{{ $t('page.pos.amount_received') }}</label>
            <input v-model.number="amountReceived" type="number" min="0" class="w-full px-4 py-3.5 border-2 border-border rounded-2xl text-2xl font-black tabular-nums text-text-primary bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 outline-none transition text-center" />
          </div>
          <div class="flex flex-wrap gap-2">
            <button @click="setExactTender" class="flex-1 min-w-[110px] px-3 py-2.5 rounded-2xl text-xs font-extrabold border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/15 active:scale-95 transition">{{ $t('page.pos.tender_exact') }}</button>
            <button v-for="q in [1000, 5000, 10000]" :key="q" @click="addTender(q)" class="flex-1 min-w-[80px] px-3 py-2.5 rounded-2xl text-xs font-extrabold border border-border bg-surface text-text-secondary hover:border-emerald-400/50 hover:text-emerald-600 active:scale-95 transition tabular-nums">+{{ q.toLocaleString('fr-FR') }}</button>
          </div>
          <div class="flex justify-between items-center text-sm rounded-2xl px-4 py-3.5 font-extrabold" :class="amountReceived >= cartTotal ? 'bg-emerald-500/10 border border-emerald-500/25 text-emerald-700 dark:text-emerald-400' : 'bg-amber-500/10 border border-amber-500/25 text-amber-700 dark:text-amber-400'">
            <span>{{ $t('page.pos.change_due') }}</span>
            <span class="text-xl tabular-nums">{{ formatMoney(Math.max(0, amountReceived - cartTotal)) }}</span>
          </div>
        </div>
        <div v-else class="flex items-center gap-2.5 p-4 text-sm font-medium text-text-secondary bg-surface-tertiary rounded-2xl">
          <span class="text-emerald-600"><CheckCircleIcon class="w-5 h-5" /></span>
          {{ $t('page.pos.card_terminal_note') }}
        </div>
        <div class="flex gap-3">
          <BaseButton variant="secondary" class="flex-1 !py-3.5" @click="showPaymentModal = false">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton class="flex-1 !py-3.5 !text-base" :loading="paying" @click="confirmPayment">
            <span class="text-white"><CheckIcon class="w-5 h-5" /></span>{{ $t('common.confirm') }} · {{ formatMoney(cartTotal) }}
          </BaseButton>
        </div>
      </div>
    </BaseModal>

    <!-- Customer Picker Modal -->
    <BaseModal :model-value="showCustomerPicker" @update:model-value="showCustomerPicker = $event" :title="$t('page.pos.select_customer')" size="md">
      <div class="space-y-3">
        <div class="relative">
          <input v-model="customerSearch" type="text" :placeholder="$t('page.pos.search_customer_ph')"
            class="w-full pl-10 pr-3 py-3 border border-border rounded-2xl text-sm font-medium bg-surface-secondary/60 focus:bg-surface focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500/60 text-text-primary placeholder:text-text-tertiary outline-none transition">
          <MagnifyingGlassIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-text-tertiary" />
        </div>
        <div class="max-h-72 overflow-y-auto space-y-1.5 custom-scrollbar pr-0.5">
          <button @click="selectCustomer(null)"
            class="w-full flex items-center gap-3 px-3 py-3 rounded-2xl text-left border transition-all"
            :class="!selectedCustomer ? 'border-emerald-500 bg-emerald-500/[0.07] shadow-[inset_0_0_0_1px_rgb(16_185_129/0.3)]' : 'border-border hover:bg-surface-tertiary'">
            <span class="flex items-center justify-center w-10 h-10 rounded-2xl bg-surface-tertiary border border-border text-text-tertiary shrink-0"><UserIcon class="w-5 h-5" /></span>
            <span class="text-sm font-extrabold text-text-primary">{{ $t('page.pos.default_customer') }}</span>
            <span v-if="!selectedCustomer" class="ml-auto text-emerald-600"><CheckIcon class="w-5 h-5" /></span>
          </button>
          <button v-for="customer in filteredCustomers" :key="customer.id" @click="selectCustomer(customer)"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-left border transition-all"
            :class="selectedCustomer?.id === customer.id ? 'border-emerald-500 bg-emerald-500/[0.07] shadow-[inset_0_0_0_1px_rgb(16_185_129/0.3)]' : 'border-border hover:bg-surface-tertiary'">
            <span class="flex items-center justify-center w-10 h-10 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 text-white text-sm font-black shrink-0">{{ (customer.name || '?').charAt(0).toUpperCase() }}</span>
            <span class="min-w-0 flex-1">
              <span class="block text-sm font-extrabold text-text-primary truncate">{{ customer.name }}</span>
              <span class="block text-xs font-medium text-text-tertiary truncate">{{ customer.phone || customer.email || '—' }}</span>
            </span>
            <span v-if="selectedCustomer?.id === customer.id" class="text-emerald-600 shrink-0"><CheckIcon class="w-5 h-5" /></span>
          </button>
          <div v-if="customersLoading" class="text-center text-sm font-semibold text-text-tertiary py-4">{{ $t('common.loading') }}</div>
          <div v-else-if="filteredCustomers.length === 0" class="text-center text-sm font-semibold text-text-tertiary py-4">{{ $t('page.pos.no_customer_found') }}</div>
        </div>
      </div>
    </BaseModal>

    <!-- Receipt Modal -->
    <BaseModal :model-value="showReceipt" @update:model-value="showReceipt = $event" :title="$t('page.pos.success_alert')" size="sm">
      <div class="space-y-4">
        <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/[0.05] p-4 font-mono text-xs text-text-primary leading-relaxed">
          <div class="text-center font-bold text-sm mb-1">SIDIBE CORPORATE</div>
          <div class="text-center text-text-tertiary mb-3">{{ $t('page.pos.ticket_word') }} {{ lastSale?.receipt_number }}</div>
          <div v-if="lastSale?.customer" class="mb-2">{{ $t('page.pos.client_label') }} : {{ lastSale.customer.name }}</div>
          <div class="border-t border-dashed border-border my-2" />
          <div v-for="item in lastSale?.items || []" :key="item.id" class="flex justify-between gap-2 mb-1">
            <span class="truncate flex-1">{{ item.product_name }} ×{{ item.quantity }}</span>
            <span class="shrink-0 tabular-nums">{{ formatMoney(item.subtotal_xof) }}</span>
          </div>
          <div class="border-t border-dashed border-border my-2" />
          <div class="flex justify-between font-bold">
            <span>{{ $t('page.pos.total_word') }}</span>
            <span class="tabular-nums">{{ formatMoney(lastSale?.total_xof) }}</span>
          </div>
          <div class="flex justify-between mt-1">
            <span>{{ $t('page.pos.paid_label') }} ({{ lastSale ? paymentLabel(lastSale.payment_method) : '' }})</span>
            <span class="tabular-nums">{{ formatMoney(lastSale?.amount_paid_xof) }}</span>
          </div>
          <div v-if="lastSale?.change_returned_xof" class="flex justify-between mt-1">
            <span>{{ $t('page.pos.change_due') }}</span>
            <span class="tabular-nums">{{ formatMoney(lastSale.change_returned_xof) }}</span>
          </div>
        </div>
        <div class="flex gap-3">
          <BaseButton variant="secondary" class="flex-1" @click="showReceipt = false">{{ $t('common.close') }}</BaseButton>
          <BaseButton class="flex-1" @click="printReceipt">
            <span class="text-white"><PrinterIcon class="w-5 h-5" /></span>{{ $t('page.pos.print_btn') }}
          </BaseButton>
        </div>
      </div>
    </BaseModal>

    <!-- Close Session Modal (counted cash) -->
    <BaseModal :model-value="showCloseModal" @update:model-value="showCloseModal = $event" :title="$t('page.pos.close_title')" :subtitle="$t('page.pos.close_subtitle')" size="sm">
      <div class="space-y-4">
        <div>
          <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1.5">{{ $t('page.pos.count_cash') }}</label>
          <input v-model.number="closingCashCount" type="number" min="0" class="w-full px-4 py-3.5 border-2 border-border rounded-2xl text-2xl font-black tabular-nums text-text-primary text-center bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 outline-none transition" />
        </div>
        <div v-if="session" class="text-sm text-text-tertiary bg-surface-tertiary rounded-2xl px-4 py-3 flex justify-between font-semibold">
          <span>{{ $t('page.pos.float_label') }}</span>
          <span class="font-black text-text-primary tabular-nums">{{ formatMoney(session.opening_cash_xof) }}</span>
        </div>
        <div class="flex gap-3">
          <BaseButton variant="secondary" class="flex-1" @click="showCloseModal = false">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton class="flex-1" variant="danger" :loading="closing" @click="confirmCloseSession">
            <span class="text-white"><CheckIcon class="w-5 h-5" /></span>{{ $t('page.pos.close_btn') }}
          </BaseButton>
        </div>
      </div>
    </BaseModal>

    <!-- Ticket Z Modal -->
    <BaseModal :model-value="showTicketZ" @update:model-value="showTicketZ = $event" :title="$t('page.pos.ticket_z')" size="sm">
      <div class="space-y-4">
        <div class="rounded-2xl border border-border/70 bg-surface-tertiary/50 p-4 font-mono text-xs text-text-primary leading-relaxed">
          <div class="text-center font-bold text-sm mb-1">SIDIBE CORPORATE</div>
          <div class="text-center text-text-tertiary mb-3">{{ formatDate(ticketZ?.closed_at) }}</div>
          <div class="flex justify-between"><span>{{ $t('page.pos.sales_word') }}</span><span class="tabular-nums">{{ ticketZ?.sales_count ?? 0 }}</span></div>
          <div class="flex justify-between"><span>{{ $t('page.pos.total_collected') }}</span><span class="tabular-nums">{{ formatMoney(ticketZ?.total_xof) }}</span></div>
          <div class="flex justify-between"><span>{{ $t('page.pos.cash_label') }}</span><span class="tabular-nums">{{ formatMoney(ticketZ?.cash_total_xof) }}</span></div>
          <div class="flex justify-between"><span>{{ $t('page.pos.card_tpe') }}</span><span class="tabular-nums">{{ formatMoney(ticketZ?.card_total_xof) }}</span></div>
          <div class="flex justify-between"><span>{{ $t('page.pos.pay_mobile') }}</span><span class="tabular-nums">{{ formatMoney(ticketZ?.mobile_money_total_xof) }}</span></div>
          <div class="border-t border-dashed border-border my-2" />
          <div class="flex justify-between"><span>{{ $t('page.pos.float_label') }}</span><span class="tabular-nums">{{ formatMoney(ticketZ?.opening_cash_xof) }}</span></div>
          <div class="flex justify-between"><span>{{ $t('page.pos.expected_cash') }}</span><span class="tabular-nums">{{ formatMoney(ticketZ?.expected_closing_cash_xof) }}</span></div>
          <div class="flex justify-between"><span>{{ $t('page.pos.actual_count') }}</span><span class="tabular-nums">{{ formatMoney(ticketZ?.closing_cash_xof) }}</span></div>
          <div class="flex justify-between font-bold mt-1"
            :class="(ticketZ?.cash_difference_xof ?? 0) === 0 ? 'text-emerald-600' : 'text-red-600'">
            <span>{{ $t('page.pos.cash_gap') }}</span>
            <span class="tabular-nums">{{ formatMoney(ticketZ?.cash_difference_xof) }}</span>
          </div>
        </div>
        <div v-if="(ticketZ?.cash_difference_xof ?? 0) !== 0" class="flex items-center gap-2.5 p-3 text-sm font-semibold text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/50 rounded-2xl border border-red-200 dark:border-red-900/70">
          <ExclamationTriangleIcon class="w-4 h-4 shrink-0" /> {{ $t('page.pos.gap_detected') }}
        </div>
        <div class="flex gap-3">
          <BaseButton variant="secondary" class="flex-1" @click="finishClosing">{{ $t('page.pos.done_btn') }}</BaseButton>
          <BaseButton class="flex-1" @click="printTicketZ">
            <span class="text-white"><PrinterIcon class="w-5 h-5" /></span>{{ $t('page.pos.print_btn') }}
          </BaseButton>
        </div>
      </div>
    </BaseModal>

    <!-- Print area (hidden, revealed only at print time) -->
    <div id="pos-print-area"></div>
  </PosLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import PosLayout from './PosLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import { MagnifyingGlassIcon, PhotoIcon, ShoppingCartIcon, TrashIcon, BanknotesIcon, CreditCardIcon, CheckIcon, UserIcon, ChevronDownIcon, CheckCircleIcon, PrinterIcon, DevicePhoneMobileIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const showToast = inject('showToast');
const { t } = useI18n();
const session = ref(null);
const products = ref([]);
const cart = ref([]);
const searchQuery = ref('');
const activeCategory = ref('');
const loading = ref(true);
const paying = ref(false);
const searchInput = ref(null);
const showPaymentModal = ref(false);
const paymentMethod = ref('cash');
const amountReceived = ref(0);

// Customer picker
const showCustomerPicker = ref(false);
const customerSearch = ref('');
const customers = ref([]);
const customersLoading = ref(false);
const selectedCustomer = ref(null);
let customerSearchTimer = null;

// Receipt
const showReceipt = ref(false);
const lastSale = ref(null);

// Close session
const showCloseModal = ref(false);
const closingCashCount = ref(0);
const closing = ref(false);
const showTicketZ = ref(false);
const ticketZ = ref(null);

onMounted(async () => {
  await checkSession();
  window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeydown);
  if (customerSearchTimer) clearTimeout(customerSearchTimer);
});

const checkSession = async () => {
  try {
    const res = await axios.get('/pos/session/current');
    if (!res.data.data) {
      router.push('/pos/session');
    } else {
      session.value = res.data.data;
      loadCatalog();
    }
  } catch (err) {
    if (err.response?.status === 401) router.push('/login');
  }
};

const loadCatalog = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/pos/catalog');
    products.value = res.data.data.map(p => ({
      ...p,
      price_xof: p.variants?.length ? p.variants[0].price_xof : (p.price_xof || 0)
    }));
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const categories = computed(() => {
  const set = new Map();
  for (const p of products.value) {
    const c = p.category_name || p.category;
    if (c && !set.has(c)) set.set(c, c);
  }
  return [...set.values()].sort((a, b) => a.localeCompare(b));
});

const filteredProducts = computed(() => {
  let list = products.value;
  if (activeCategory.value) {
    list = list.filter(p => (p.category_name || p.category) === activeCategory.value);
  }
  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(p =>
    p.name.toLowerCase().includes(q) ||
    (p.sku && p.sku.toLowerCase().includes(q)) ||
    (p.barcode && p.barcode.toLowerCase().includes(q))
  );
});

const stockOf = (p) => {
  for (const k of ['stock_quantity', 'stock', 'quantity', 'available_qty']) {
    if (typeof p[k] === 'number') return p[k];
  }
  return null;
};

const addToCart = (product) => {
  const existing = cart.value.find(i => i.product_id === product.id);
  if (existing) {
    existing.quantity++;
  } else {
    cart.value.unshift({
      product_id: product.id,
      name: product.name,
      price_xof: product.price_xof || 0,
      quantity: 1
    });
  }
  searchQuery.value = '';
};

// Scanner / recherche exacte : Enter ajoute directement le produit au panier
const searchExactMatch = () => {
  const q = searchQuery.value.trim().toLowerCase();
  if (q.length < 2) return;
  const p = products.value.find(p =>
    p.name.toLowerCase() === q ||
    (p.sku && p.sku.toLowerCase() === q) ||
    (p.barcode && p.barcode.toLowerCase() === q)
  );
  if (p) addToCart(p);
};

const updateQty = (index, delta) => {
  cart.value[index].quantity += delta;
  if (cart.value[index].quantity <= 0) {
    cart.value.splice(index, 1);
  }
};

const removeFromCart = (index) => {
  cart.value.splice(index, 1);
};

const clearCart = () => {
  cart.value = [];
};

const cartTotal = computed(() => {
  return cart.value.reduce((total, item) => total + (item.price_xof * item.quantity), 0);
});

const cartCount = computed(() => {
  return cart.value.reduce((n, item) => n + (item.quantity || 0), 0);
});

const setExactTender = () => {
  amountReceived.value = cartTotal.value;
};

const addTender = (n) => {
  amountReceived.value = (Number(amountReceived.value) || 0) + n;
};

const formatMoney = (amount) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(amount || 0);
};

const paymentLabel = (method) => {
  const labels = { cash: t('page.pos.pay_cash'), card: t('page.pos.card_tpe'), mobile_money: t('page.pos.pay_mobile') };
  return labels[method] || method || '';
};

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// ---------- Customer picker ----------
const openCustomerPicker = () => {
  showCustomerPicker.value = true;
  customerSearch.value = '';
  fetchCustomers('');
};

const filteredCustomers = computed(() => {
  const q = customerSearch.value.trim().toLowerCase();
  if (!q) return customers.value;
  return customers.value.filter(c =>
    c.name?.toLowerCase().includes(q) ||
    c.phone?.toLowerCase().includes(q) ||
    c.email?.toLowerCase().includes(q)
  );
});

const fetchCustomers = async (search) => {
  customersLoading.value = true;
  try {
    const { data } = await axios.get('/customers', { params: { search: search || undefined, per_page: 50, is_active: 1 } });
    customers.value = data.data ?? [];
  } catch {
    customers.value = [];
  } finally {
    customersLoading.value = false;
  }
};

watch(customerSearch, (val) => {
  clearTimeout(customerSearchTimer);
  customerSearchTimer = setTimeout(() => fetchCustomers(val), 350);
});

const selectCustomer = (customer) => {
  selectedCustomer.value = customer;
  showCustomerPicker.value = false;
  showToast(customer ? t('page.pos.customer_selected', { name: customer.name }) : t('page.pos.counter_customer'));
};

// ---------- Payment ----------
function openPaymentModal(method) {
  paymentMethod.value = method;
  amountReceived.value = cartTotal.value;
  showPaymentModal.value = true;
}

async function confirmPayment() {
  if (cart.value.length === 0 || paying.value) return;
  if (paymentMethod.value === 'cash' && amountReceived.value < cartTotal.value) {
    showToast(t('page.pos.amount_insufficient'), 'error');
    return;
  }
  paying.value = true;

  const payload = {
    pos_session_id: session.value.id,
    payment_method: paymentMethod.value,
    amount_paid_xof: paymentMethod.value === 'cash' ? amountReceived.value : cartTotal.value,
    items: cart.value.map(i => ({
      product_id: i.product_id,
      quantity: i.quantity,
      unit_price_xof: i.price_xof
    }))
  };
  if (selectedCustomer.value) payload.customer_id = selectedCustomer.value.id;

  try {
    const res = await axios.post('/pos/sale', payload);
    cart.value = [];
    showPaymentModal.value = false;
    lastSale.value = res.data.data;
    showReceipt.value = true;
  } catch (err) {
    const data = err.response?.data;
    if (data?.errors?.stock) {
      showToast(data.message + ' • ' + data.errors.stock.join(' • '), 'error');
    } else {
      showToast(data?.message || t('page.pos.sale_error'), 'error');
    }
  } finally {
    paying.value = false;
  }
}

// ---------- Close session & Ticket Z ----------
const openCloseModal = () => {
  closingCashCount.value = 0;
  showCloseModal.value = true;
};

const confirmCloseSession = async () => {
  if (closing.value) return;
  closing.value = true;
  try {
    const res = await axios.post(`/pos/session/${session.value.id}/close`, {
      closing_cash_xof: closingCashCount.value
    });
    ticketZ.value = res.data.summary;
    ticketZ.value.closed_at = res.data.data.closed_at;
    ticketZ.value.closing_cash_xof = res.data.data.closing_cash_xof;
    showCloseModal.value = false;
    showTicketZ.value = true;
  } catch (err) {
    showToast(t('page.pos.close_error_toast'), 'error');
  } finally {
    closing.value = false;
  }
};

const finishClosing = () => {
  showTicketZ.value = false;
  showToast(t('page.pos.closed_ok'));
  router.push('/dashboard');
};

// ---------- Printing ----------
function printHtml(title, lines) {
  const area = document.getElementById('pos-print-area');
  if (!area) return;
  area.innerHTML = `
    <div style="width:80mm;font-family:monospace;font-size:12px;color:#000;">
      <div style="text-align:center;font-weight:bold;font-size:14px;">SIDIBE CORPORATE</div>
      <div style="text-align:center;margin-bottom:6px;">${title}</div>
      <div style="border-top:1px dashed #000;margin:4px 0;"></div>
      ${lines}
    </div>`;
  window.print();
}

const printReceipt = () => {
  const s = lastSale.value;
  if (!s) return;
  const lines = [
    `<div style="text-align:center;">${t('page.pos.ticket_word')} ${s.receipt_number}</div>`,
    s.customer ? `<div>${t('page.pos.client_label')} : ${s.customer.name}</div>` : '',
    `<div style="border-top:1px dashed #000;margin:4px 0;"></div>`,
    ...(s.items || []).map(i =>
      `<div style="display:flex;justify-content:space-between;"><span>${i.product_name} x${i.quantity}</span><span>${formatMoney(i.subtotal_xof)}</span></div>`
    ),
    `<div style="border-top:1px dashed #000;margin:4px 0;"></div>`,
    `<div style="display:flex;justify-content:space-between;font-weight:bold;"><span>${t('page.pos.total_word')}</span><span>${formatMoney(s.total_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.paid_label')} (${paymentLabel(s.payment_method)})</span><span>${formatMoney(s.amount_paid_xof)}</span></div>`,
    s.change_returned_xof ? `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.change_due')}</span><span>${formatMoney(s.change_returned_xof)}</span></div>` : '',
    `<div style="border-top:1px dashed #000;margin:6px 0;"></div>`,
    `<div style="text-align:center;">${t('page.pos.thanks')}</div>`,
  ].join('');
  printHtml(t('page.pos.receipt_title', { number: s.receipt_number }), lines);
};

const printTicketZ = () => {
  const z = ticketZ.value;
  if (!z) return;
  const lines = [
    `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.sales_word')}</span><span>${z.sales_count ?? 0}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.total_collected')}</span><span>${formatMoney(z.total_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.cash_label')}</span><span>${formatMoney(z.cash_total_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.card_tpe')}</span><span>${formatMoney(z.card_total_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.pay_mobile')}</span><span>${formatMoney(z.mobile_money_total_xof)}</span></div>`,
    `<div style="border-top:1px dashed #000;margin:4px 0;"></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.float_label')}</span><span>${formatMoney(z.opening_cash_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.expected_cash')}</span><span>${formatMoney(z.expected_closing_cash_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>${t('page.pos.actual_count')}</span><span>${formatMoney(z.closing_cash_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;font-weight:bold;"><span>${t('page.pos.cash_gap')}</span><span>${formatMoney(z.cash_difference_xof)}</span></div>`,
  ].join('');
  printHtml(`${t('page.pos.ticket_word')} Z — ${formatDate(z.closed_at)}`, lines);
};

// ---------- Barcode / keyboard ----------
let barcodeBuffer = '';
let barcodeTimeout = null;

const handleGlobalKeydown = (e) => {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

  if (e.key === 'Enter' && barcodeBuffer.length > 3) {
    const p = products.value.find(p => p.sku === barcodeBuffer || p.barcode === barcodeBuffer);
    if (p) addToCart(p);
    barcodeBuffer = '';
    return;
  }

  if (e.key.length === 1) {
    barcodeBuffer += e.key;
    clearTimeout(barcodeTimeout);
    barcodeTimeout = setTimeout(() => { barcodeBuffer = ''; }, 100);
  }
};
</script>

<style>
/* Impression : seul le contenu de #pos-print-area est imprimé */
@media print {
  body * {
    visibility: hidden;
  }
  #pos-print-area, #pos-print-area * {
    visibility: visible;
  }
  #pos-print-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 80mm;
  }
}

/* Transition d'ajout au panier */
.cart-enter-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.cart-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; position: absolute; }
.cart-enter-from { opacity: 0; transform: translateX(12px); }
.cart-leave-to { opacity: 0; transform: translateX(12px); }
.cart-move { transition: transform 0.25s ease; }
</style>
