<template>
  <PosLayout :session="session">
    <!-- Left: Products -->
    <div class="flex-1 flex flex-col bg-surface-secondary border-r border-border">
      <div class="p-4 bg-surface border-b flex gap-4 shadow-sm z-10">
        <div class="relative flex-1">
          <input v-model="searchQuery" @keydown.enter="searchExactMatch" type="text" placeholder="Rechercher un produit ou scanner un code-barres..."
            class="w-full pl-10 pr-4 py-3 bg-surface-tertiary border border-transparent rounded-xl focus:bg-surface focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-text-primary placeholder:text-text-tertiary font-medium outline-none"
            ref="searchInput">
          <span class="absolute left-3.5 top-3.5 text-text-tertiary"><MagnifyingGlassIcon class="w-5 h-5" /></span>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto p-4">
        <div v-if="loading" class="flex justify-center items-center h-full text-text-tertiary">Chargement du catalogue...</div>
        <div v-else-if="filteredProducts.length === 0" class="flex justify-center items-center h-full text-text-tertiary">Aucun produit trouvé.</div>
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 auto-rows-max">
          <div v-for="product in filteredProducts" :key="product.id" @click="addToCart(product)"
            class="bg-surface rounded-xl shadow-sm border border-border/60 overflow-hidden cursor-pointer group transition-all duration-300 ease-out will-change-transform hover:-translate-y-1 hover:shadow-lg hover:shadow-emerald-500/10 hover:border-emerald-400/70 active:scale-[0.97]">
            <div class="h-28 bg-surface-tertiary flex items-center justify-center relative overflow-hidden">
              <img v-if="product.image" :src="product.image.startsWith('http') ? product.image : '/storage/' + product.image"
                class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-110" :alt="product.name">
              <span v-else class="text-text-tertiary transition-transform duration-500 ease-out group-hover:scale-110"><PhotoIcon class="w-10 h-10" /></span>
              <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none"></div>
              <div v-if="product.price_xof !== undefined" class="absolute bottom-2 right-2 bg-emerald-600 text-white text-xs font-bold px-2 py-1 rounded shadow-sm z-10 transition-all duration-300 group-hover:scale-110 group-hover:shadow-md group-hover:shadow-emerald-600/40">{{ formatMoney(product.price_xof) }}</div>
            </div>
            <div class="p-3">
              <h3 class="font-semibold text-text-primary text-sm line-clamp-2 leading-tight transition-colors duration-300 group-hover:text-emerald-600">{{ product.name }}</h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Cart & Checkout -->
    <div class="w-96 bg-surface flex flex-col shadow-xl z-20">
      <!-- Customer selector -->
      <div class="border-b border-border px-4 py-2.5 bg-surface-secondary/60">
        <button @click="openCustomerPicker" class="w-full flex items-center gap-2.5 text-left group">
          <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 shrink-0">
            <UserIcon class="w-4.5 h-4.5" />
          </span>
          <span class="flex-1 min-w-0">
            <span class="block text-xs text-text-tertiary">Client</span>
            <span class="block font-semibold text-text-primary text-sm truncate">
              {{ selectedCustomer?.name || 'Comptoir (client par défaut)' }}
            </span>
          </span>
          <ChevronDownIcon class="w-4 h-4 text-text-tertiary group-hover:text-text-secondary" />
        </button>
      </div>

      <div class="flex-1 overflow-y-auto bg-surface-secondary p-2">
        <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center text-text-tertiary p-6 text-center">
          <span class="text-text-tertiary mb-4"><ShoppingCartIcon class="w-16 h-16" /></span>
          <p class="font-medium">Le panier est vide</p>
          <p class="text-sm mt-1">Sélectionnez des produits ou scannez un code-barres.</p>
        </div>

        <div v-for="(item, index) in cart" :key="index" class="bg-surface p-3 rounded-xl shadow-sm border border-border/60 mb-2 flex items-center gap-3">
          <div class="flex-1 min-w-0">
            <h4 class="font-semibold text-text-primary truncate text-sm">{{ item.name }}</h4>
            <div class="text-emerald-600 font-bold text-sm mt-0.5">{{ formatMoney(item.price_xof) }}</div>
          </div>
          <div class="flex items-center bg-surface-tertiary rounded-lg p-0.5">
            <button @click="updateQty(index, -1)" class="w-8 h-8 flex items-center justify-center text-text-secondary hover:bg-surface hover:shadow-sm rounded-md font-bold">-</button>
            <span class="w-8 text-center font-bold text-text-primary text-sm">{{ item.quantity }}</span>
            <button @click="updateQty(index, 1)" class="w-8 h-8 flex items-center justify-center text-text-secondary hover:bg-surface hover:shadow-sm rounded-md font-bold">+</button>
          </div>
          <button @click="removeFromCart(index)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
            <span class="text-current"><TrashIcon class="w-5 h-5" /></span>
          </button>
        </div>
      </div>

      <!-- Payment Modal -->
      <BaseModal :model-value="showPaymentModal" @update:model-value="showPaymentModal = $event" :title="'Paiement par ' + paymentLabel(paymentMethod)" subtitle="Montant à encaisser" size="sm">
        <div class="space-y-4">
          <div class="text-center">
            <p class="text-4xl font-black text-emerald-600 tracking-tight">{{ formatMoney(cartTotal) }}</p>
            <p v-if="selectedCustomer" class="text-sm text-text-tertiary mt-1">Client : {{ selectedCustomer.name }}</p>
          </div>
          <div v-if="paymentMethod === 'cash'" class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Montant reçu</label>
              <input v-model.number="amountReceived" type="number" min="0" class="w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary text-xl font-bold" />
            </div>
            <div class="flex justify-between text-sm pt-2 border-t border-border">
              <span class="text-text-tertiary">Rendu</span>
              <span class="font-bold text-text-primary">{{ formatMoney(Math.max(0, amountReceived - cartTotal)) }}</span>
            </div>
          </div>
          <div v-else class="flex items-center gap-2.5 p-3 text-sm text-text-secondary bg-surface-tertiary rounded-xl">
            <span class="text-emerald-600"><CheckCircleIcon class="w-5 h-5" /></span>
            Le paiement est confirmé une fois l'encaissement effectué sur le terminal.
          </div>
          <div class="flex gap-3 pt-2">
            <BaseButton variant="secondary" class="flex-1" @click="showPaymentModal = false">Annuler</BaseButton>
            <BaseButton class="flex-1" :loading="paying" @click="confirmPayment">
              <span class="text-white"><CheckIcon class="w-5 h-5" /></span>Confirmer
            </BaseButton>
          </div>
        </div>
      </BaseModal>

      <!-- Customer Picker Modal -->
      <BaseModal :model-value="showCustomerPicker" @update:model-value="showCustomerPicker = $event" title="Sélectionner un client" size="md">
        <div class="space-y-3">
          <div class="relative">
            <input v-model="customerSearch" type="text" placeholder="Rechercher par nom, téléphone ou email..."
              class="w-full pl-9 pr-3 py-2.5 border border-border rounded-xl text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary placeholder:text-text-tertiary outline-none">
            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-text-tertiary" />
          </div>
          <div class="max-h-64 overflow-y-auto space-y-1.5">
            <button @click="selectCustomer(null)"
              class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-left border transition-colors"
              :class="!selectedCustomer ? 'border-emerald-500 bg-emerald-50/50' : 'border-border hover:bg-surface-tertiary'">
              <span class="text-sm font-medium text-text-primary">Comptoir (client par défaut)</span>
              <span v-if="!selectedCustomer" class="ml-auto text-emerald-600"><CheckIcon class="w-4 h-4" /></span>
            </button>
            <button v-for="customer in filteredCustomers" :key="customer.id" @click="selectCustomer(customer)"
              class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-left border transition-colors"
              :class="selectedCustomer?.id === customer.id ? 'border-emerald-500 bg-emerald-50/50' : 'border-border hover:bg-surface-tertiary'">
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-medium text-text-primary truncate">{{ customer.name }}</span>
                <span class="block text-xs text-text-tertiary truncate">{{ customer.phone || customer.email || '—' }}</span>
              </span>
              <span v-if="selectedCustomer?.id === customer.id" class="text-emerald-600 shrink-0"><CheckIcon class="w-4 h-4" /></span>
            </button>
            <div v-if="customersLoading" class="text-center text-sm text-text-tertiary py-4">Chargement...</div>
            <div v-else-if="filteredCustomers.length === 0" class="text-center text-sm text-text-tertiary py-4">Aucun client trouvé.</div>
          </div>
        </div>
      </BaseModal>

      <!-- Receipt Modal -->
      <BaseModal :model-value="showReceipt" @update:model-value="showReceipt = $event" title="Encaissement réussi" size="sm">
        <div class="space-y-4">
          <div class="rounded-xl bg-surface-tertiary p-4 font-mono text-xs text-text-primary leading-relaxed">
            <div class="text-center font-bold text-sm mb-2">SIDIBE CORPORATE</div>
            <div class="text-center text-text-tertiary mb-3">Ticket {{ lastSale?.receipt_number }}</div>
            <div v-if="lastSale?.customer" class="mb-2">Client : {{ lastSale.customer.name }}</div>
            <div class="border-t border-dashed border-border my-2" />
            <div v-for="item in lastSale?.items || []" :key="item.id" class="flex justify-between gap-2 mb-1">
              <span class="truncate flex-1">{{ item.product_name }} ×{{ item.quantity }}</span>
              <span class="shrink-0">{{ formatMoney(item.subtotal_xof) }}</span>
            </div>
            <div class="border-t border-dashed border-border my-2" />
            <div class="flex justify-between font-bold">
              <span>Total</span>
              <span>{{ formatMoney(lastSale?.total_xof) }}</span>
            </div>
            <div class="flex justify-between mt-1">
              <span>Payé ({{ lastSale ? paymentLabel(lastSale.payment_method) : '' }})</span>
              <span>{{ formatMoney(lastSale?.amount_paid_xof) }}</span>
            </div>
            <div v-if="lastSale?.change_returned_xof" class="flex justify-between mt-1">
              <span>Rendu</span>
              <span>{{ formatMoney(lastSale.change_returned_xof) }}</span>
            </div>
          </div>
          <div class="flex gap-3">
            <BaseButton variant="secondary" class="flex-1" @click="showReceipt = false">Fermer</BaseButton>
            <BaseButton class="flex-1" @click="printReceipt">
              <span class="text-white"><PrinterIcon class="w-5 h-5" /></span>Imprimer
            </BaseButton>
          </div>
        </div>
      </BaseModal>

      <!-- Close Session Modal (counted cash) -->
      <BaseModal :model-value="showCloseModal" @update:model-value="showCloseModal = $event" title="Clôturer la caisse" subtitle="Saisissez le comptage d'espèces pour éditer le Ticket Z" size="sm">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-text-secondary mb-1">Comptage des espèces (FCFA)</label>
            <input v-model.number="closingCashCount" type="number" min="0" class="w-full px-3 py-2.5 border border-border rounded-xl text-lg font-bold text-text-primary bg-surface focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
          </div>
          <div v-if="session" class="text-sm text-text-tertiary bg-surface-tertiary rounded-xl p-3 flex justify-between">
            <span>Fond de caisse</span>
            <span class="font-bold text-text-primary">{{ formatMoney(session.opening_cash_xof) }}</span>
          </div>
          <div class="flex gap-3">
            <BaseButton variant="secondary" class="flex-1" @click="showCloseModal = false">Annuler</BaseButton>
            <BaseButton class="flex-1" variant="danger" :loading="closing" @click="confirmCloseSession">
              <span class="text-white"><CheckIcon class="w-5 h-5" /></span>Clôturer
            </BaseButton>
          </div>
        </div>
      </BaseModal>

      <!-- Ticket Z Modal -->
      <BaseModal :model-value="showTicketZ" @update:model-value="showTicketZ = $event" title="Ticket Z — Caisse clôturée" size="sm">
        <div class="space-y-4">
          <div class="rounded-xl bg-surface-tertiary p-4 font-mono text-xs text-text-primary leading-relaxed">
            <div class="text-center font-bold text-sm mb-1">SIDIBE CORPORATE</div>
            <div class="text-center text-text-tertiary mb-3">{{ formatDate(ticketZ?.closed_at) }}</div>
            <div class="flex justify-between"><span>Ventes</span><span>{{ ticketZ?.sales_count ?? 0 }}</span></div>
            <div class="flex justify-between"><span>Total encaissé</span><span>{{ formatMoney(ticketZ?.total_xof) }}</span></div>
            <div class="flex justify-between"><span>Espèces</span><span>{{ formatMoney(ticketZ?.cash_total_xof) }}</span></div>
            <div class="flex justify-between"><span>Carte (TPE)</span><span>{{ formatMoney(ticketZ?.card_total_xof) }}</span></div>
            <div class="flex justify-between"><span>Mobile Money</span><span>{{ formatMoney(ticketZ?.mobile_money_total_xof) }}</span></div>
            <div class="border-t border-dashed border-border my-2" />
            <div class="flex justify-between"><span>Fond de caisse</span><span>{{ formatMoney(ticketZ?.opening_cash_xof) }}</span></div>
            <div class="flex justify-between"><span>Caisse attendue</span><span>{{ formatMoney(ticketZ?.expected_closing_cash_xof) }}</span></div>
            <div class="flex justify-between"><span>Comptage réel</span><span>{{ formatMoney(ticketZ?.closing_cash_xof) }}</span></div>
            <div class="flex justify-between font-bold mt-1"
              :class="(ticketZ?.cash_difference_xof ?? 0) === 0 ? 'text-emerald-600' : 'text-red-600'">
              <span>Écart de caisse</span>
              <span>{{ formatMoney(ticketZ?.cash_difference_xof) }}</span>
            </div>
          </div>
          <div v-if="(ticketZ?.cash_difference_xof ?? 0) !== 0" class="flex items-center gap-2.5 p-3 text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/50 rounded-xl border border-red-100 dark:border-red-900/70">
            <ExclamationTriangleIcon class="w-4 h-4 shrink-0" /> Un écart de caisse a été détecté.
          </div>
          <div class="flex gap-3">
            <BaseButton variant="secondary" class="flex-1" @click="finishClosing">Terminer</BaseButton>
            <BaseButton class="flex-1" @click="printTicketZ">
              <span class="text-white"><PrinterIcon class="w-5 h-5" /></span>Imprimer
            </BaseButton>
          </div>
        </div>
      </BaseModal>

      <!-- Totals & Pay -->
      <div class="border-t border-border bg-surface p-5">
        <div class="flex justify-between items-end mb-5">
          <span class="text-text-tertiary font-medium">Total à payer</span>
          <span class="text-4xl font-black text-emerald-600 tracking-tight">{{ formatMoney(cartTotal) }}</span>
        </div>

        <div class="grid grid-cols-3 gap-3 mb-4">
          <button @click="openPaymentModal('cash')" :disabled="cart.length === 0 || paying"
            class="py-3.5 rounded-xl font-bold text-sm flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="cart.length ? 'bg-gray-900 text-white hover:bg-gray-800 shadow-lg hover:shadow-xl' : 'bg-surface-tertiary text-text-tertiary'">
            <span class="text-current"><BanknotesIcon class="w-5 h-5" /></span>Espèces
          </button>
          <button @click="openPaymentModal('card')" :disabled="cart.length === 0 || paying"
            class="py-3.5 rounded-xl font-bold text-sm flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="cart.length ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-lg hover:shadow-xl' : 'bg-surface-tertiary text-text-tertiary'">
            <span class="text-current"><CreditCardIcon class="w-5 h-5" /></span>Carte
          </button>
          <button @click="openPaymentModal('mobile_money')" :disabled="cart.length === 0 || paying"
            class="py-3.5 rounded-xl font-bold text-sm flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="cart.length ? 'bg-emerald-700 text-white hover:bg-emerald-800 shadow-lg hover:shadow-xl' : 'bg-surface-tertiary text-text-tertiary'">
            <span class="text-current"><DevicePhoneMobileIcon class="w-5 h-5" /></span>M-Money
          </button>
        </div>

        <button @click="openCloseModal" :disabled="closing"
          class="w-full py-3 text-red-600 bg-surface hover:bg-red-50 font-bold rounded-xl text-sm transition-colors border-2 border-red-100 disabled:opacity-50">
          Clôturer la Caisse
        </button>
      </div>
    </div>

    <!-- Print area (hidden, revealed only at print time) -->
    <div id="pos-print-area"></div>
  </PosLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PosLayout from './PosLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import { MagnifyingGlassIcon, PhotoIcon, ShoppingCartIcon, TrashIcon, BanknotesIcon, CreditCardIcon, CheckIcon, UserIcon, ChevronDownIcon, CheckCircleIcon, PrinterIcon, DevicePhoneMobileIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const showToast = inject('showToast');
const session = ref(null);
const products = ref([]);
const cart = ref([]);
const searchQuery = ref('');
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

const filteredProducts = computed(() => {
  if (!searchQuery.value) return products.value;
  const q = searchQuery.value.toLowerCase();
  return products.value.filter(p =>
    p.name.toLowerCase().includes(q) ||
    (p.sku && p.sku.toLowerCase().includes(q)) ||
    (p.barcode && p.barcode.toLowerCase().includes(q))
  );
});

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

const cartTotal = computed(() => {
  return cart.value.reduce((total, item) => total + (item.price_xof * item.quantity), 0);
});

const formatMoney = (amount) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(amount || 0);
};

const paymentLabel = (method) => {
  const labels = { cash: 'Espèces', card: 'Carte (TPE)', mobile_money: 'Mobile Money' };
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
  showToast(customer ? `Client sélectionné : ${customer.name}` : 'Client comptoir sélectionné');
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
    showToast('Montant reçu insuffisant.', 'error');
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
      showToast(data?.message || 'Erreur lors de l\'encaissement', 'error');
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
    showToast('Erreur lors de la clôture', 'error');
  } finally {
    closing.value = false;
  }
};

const finishClosing = () => {
  showTicketZ.value = false;
  showToast('Caisse clôturée avec succès.');
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
    `<div style="text-align:center;">Ticket ${s.receipt_number}</div>`,
    s.customer ? `<div>Client : ${s.customer.name}</div>` : '',
    `<div style="border-top:1px dashed #000;margin:4px 0;"></div>`,
    ...(s.items || []).map(i =>
      `<div style="display:flex;justify-content:space-between;"><span>${i.product_name} x${i.quantity}</span><span>${formatMoney(i.subtotal_xof)}</span></div>`
    ),
    `<div style="border-top:1px dashed #000;margin:4px 0;"></div>`,
    `<div style="display:flex;justify-content:space-between;font-weight:bold;"><span>Total</span><span>${formatMoney(s.total_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>Payé (${paymentLabel(s.payment_method)})</span><span>${formatMoney(s.amount_paid_xof)}</span></div>`,
    s.change_returned_xof ? `<div style="display:flex;justify-content:space-between;"><span>Rendu</span><span>${formatMoney(s.change_returned_xof)}</span></div>` : '',
    `<div style="border-top:1px dashed #000;margin:6px 0;"></div>`,
    `<div style="text-align:center;">Merci de votre visite !</div>`,
  ].join('');
  printHtml(`Reçu ${s.receipt_number}`, lines);
};

const printTicketZ = () => {
  const t = ticketZ.value;
  if (!t) return;
  const lines = [
    `<div style="display:flex;justify-content:space-between;"><span>Ventes</span><span>${t.sales_count ?? 0}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>Total encaissé</span><span>${formatMoney(t.total_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>Espèces</span><span>${formatMoney(t.cash_total_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>Carte (TPE)</span><span>${formatMoney(t.card_total_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>Mobile Money</span><span>${formatMoney(t.mobile_money_total_xof)}</span></div>`,
    `<div style="border-top:1px dashed #000;margin:4px 0;"></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>Fond de caisse</span><span>${formatMoney(t.opening_cash_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>Caisse attendue</span><span>${formatMoney(t.expected_closing_cash_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;"><span>Comptage réel</span><span>${formatMoney(t.closing_cash_xof)}</span></div>`,
    `<div style="display:flex;justify-content:space-between;font-weight:bold;"><span>Écart de caisse</span><span>${formatMoney(t.cash_difference_xof)}</span></div>`,
  ].join('');
  printHtml(`Ticket Z — ${formatDate(t.closed_at)}`, lines);
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
</style>
