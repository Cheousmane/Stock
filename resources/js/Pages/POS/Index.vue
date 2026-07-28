<template>
  <PosLayout :session="session">
    <!-- Left: Products -->
    <div class="flex-1 flex flex-col bg-surface-secondary border-r border-border">
      <div class="p-4 bg-surface border-b flex gap-4 shadow-sm z-10">
        <div class="relative flex-1">
          <input v-model="searchQuery" type="text" placeholder="Rechercher un produit ou scanner un code-barres..."
            class="w-full pl-10 pr-4 py-3 bg-surface-tertiary border border-transparent rounded-xl focus:bg-surface focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-text-primary placeholder:text-text-tertiary font-medium transition-all outline-none"
            ref="searchInput">
          <span class="absolute left-3.5 top-3.5 text-text-tertiary"><MagnifyingGlassIcon class="w-5 h-5" /></span>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto p-4">
        <div v-if="loading" class="flex justify-center items-center h-full text-text-tertiary">Chargement du catalogue...</div>
        <div v-else-if="filteredProducts.length === 0" class="flex justify-center items-center h-full text-text-tertiary">Aucun produit trouvé.</div>
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 auto-rows-max">
          <div v-for="product in filteredProducts" :key="product.id" @click="addToCart(product)"
            class="bg-surface rounded-xl shadow-sm border border-border/60 overflow-hidden cursor-pointer hover:shadow-md hover:border-emerald-300 hover:-translate-y-0.5 transition-all group">
            <div class="h-28 bg-surface-tertiary flex items-center justify-center relative overflow-hidden">
              <img v-if="product.image" :src="product.image.startsWith('http') ? product.image : '/storage/' + product.image"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" :alt="product.name">
              <span v-else class="text-text-tertiary"><PhotoIcon class="w-10 h-10" /></span>
              <div v-if="product.price_xof !== undefined" class="absolute bottom-2 right-2 bg-emerald-600 text-white text-xs font-bold px-2 py-1 rounded shadow-sm z-10">{{ formatMoney(product.price_xof) }}</div>
            </div>
            <div class="p-3">
              <h3 class="font-semibold text-text-primary text-sm line-clamp-2 leading-tight">{{ product.name }}</h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Cart & Checkout -->
    <div class="w-96 bg-surface flex flex-col shadow-xl z-20">
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
            <button @click="updateQty(index, -1)" class="w-8 h-8 flex items-center justify-center text-text-secondary hover:bg-surface hover:shadow-sm rounded-md transition-all font-bold">-</button>
            <span class="w-8 text-center font-bold text-text-primary text-sm">{{ item.quantity }}</span>
            <button @click="updateQty(index, 1)" class="w-8 h-8 flex items-center justify-center text-text-secondary hover:bg-surface hover:shadow-sm rounded-md transition-all font-bold">+</button>
          </div>
          <button @click="removeFromCart(index)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
            <span class="text-current"><TrashIcon class="w-5 h-5" /></span>
          </button>
        </div>
      </div>

      <!-- Payment Modal -->
      <BaseModal v-model="showPaymentModal" :title="'Paiement par ' + (paymentMethod === 'cash' ? 'Espèces' : 'Carte')" subtitle="Montant à encaisser" size="sm">
        <div class="space-y-4">
          <div class="text-center">
            <p class="text-4xl font-black text-emerald-600 tracking-tight">{{ formatMoney(cartTotal) }}</p>
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
          <div class="flex gap-3 pt-2">
            <BaseButton variant="secondary" class="flex-1" @click="showPaymentModal = false">Annuler</BaseButton>
            <BaseButton class="flex-1" :loading="paying" @click="confirmPayment">
              <span class="text-white"><CheckIcon class="w-5 h-5" /></span>Confirmer
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

        <div class="grid grid-cols-2 gap-3 mb-4">
          <button @click="openPaymentModal('cash')" :disabled="cart.length === 0 || paying"
            class="py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            :class="cart.length ? 'bg-gray-900 text-white hover:bg-gray-800 shadow-lg hover:shadow-xl hover:-translate-y-0.5' : 'bg-surface-tertiary text-text-tertiary'">
            <span class="text-current"><BanknotesIcon class="w-6 h-6" /></span>Espèces
          </button>
          <button @click="openPaymentModal('card')" :disabled="cart.length === 0 || paying"
            class="py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            :class="cart.length ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-lg hover:shadow-xl hover:-translate-y-0.5' : 'bg-surface-tertiary text-text-tertiary'">
            <span class="text-current"><CreditCardIcon class="w-6 h-6" /></span>Carte (TPE)
          </button>
        </div>

        <button @click="closeSession" class="w-full py-3 text-red-600 bg-surface hover:bg-red-50 font-bold rounded-xl text-sm transition-colors border-2 border-red-100">
          Clôturer la Caisse
        </button>
      </div>
    </div>
  </PosLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PosLayout from './PosLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import { MagnifyingGlassIcon, PhotoIcon, ShoppingCartIcon, TrashIcon, BanknotesIcon, CreditCardIcon, CheckIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
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

onMounted(async () => {
  await checkSession();
  window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeydown);
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
    (p.sku && p.sku.toLowerCase().includes(q))
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

function openPaymentModal(method) {
  paymentMethod.value = method;
  amountReceived.value = cartTotal.value;
  showPaymentModal.value = true;
}

async function confirmPayment() {
  if (cart.value.length === 0 || paying.value) return;
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

  try {
    await axios.post('/pos/sale', payload);
    cart.value = [];
    showPaymentModal.value = false;
    alert('Encaissement réussi !');
  } catch (err) {
    const data = err.response?.data;
    if (data?.errors?.stock) {
      alert(data.message + '\n\n• ' + data.errors.stock.join('\n• '));
    } else {
      alert(data?.message || 'Erreur lors de l\'encaissement');
    }
  } finally {
    paying.value = false;
  }
}

const closeSession = async () => {
  if (!confirm('Voulez-vous clôturer la caisse et éditer le Ticket Z ?')) return;
  try {
    await axios.post(`/pos/session/${session.value.id}/close`, {
      closing_cash_xof: 0
    });
    router.push('/dashboard');
  } catch (err) {
    alert('Erreur lors de la clôture');
  }
};

let barcodeBuffer = '';
let barcodeTimeout = null;

const handleGlobalKeydown = (e) => {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

  if (e.key === 'Enter' && barcodeBuffer.length > 3) {
    const p = products.value.find(p => p.sku === barcodeBuffer);
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
