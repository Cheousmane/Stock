<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <div v-if="loading" class="py-12">
        <BaseSkeleton :count="4" />
      </div>

      <div v-else-if="error" class="py-12 text-center text-text-tertiary">{{ error }}</div>

      <template v-else-if="product">
        <BasePageHeader :title="product.name" :subtitle="$t('page.products.detail')">
          <template #actions>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'ProductEdit', params: { id: product.id } }">
              <span class="text-current"><PencilIcon class="w-4 h-4" /></span>{{ $t('common.edit') }}
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'Products' }">
              <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.back') }}
            </BaseButton>
          </template>
        </BasePageHeader>

        <div v-if="product.image_url" class="mb-6">
          <img :src="product.image_url" class="w-48 h-48 object-cover rounded-xl border border-border shadow-sm" />
        </div>

        <BaseCard :title="$t('page.products.information')" padding="lg">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-text-tertiary">{{ $t('page.products.name_label') }}</span><p class="font-medium text-text-primary">{{ product.name }}</p></div>
            <div><span class="text-text-tertiary">{{ $t('page.products.sku_label') }}</span><p class="text-text-primary">{{ product.sku || '—' }}</p></div>
            <div><span class="text-text-tertiary">{{ $t('page.products.barcode_label') }}</span><p class="text-text-primary font-mono">{{ product.barcode || '—' }}</p></div>
            <div><span class="text-text-tertiary">{{ $t('page.products.category_label') }}</span><p class="text-text-primary">{{ product.category_name || '—' }}</p></div>
            <div class="col-span-2"><span class="text-text-tertiary">{{ $t('page.products.description_label') }}</span><p class="text-text-primary">{{ product.description || '—' }}</p></div>
            <div><span class="text-text-tertiary">{{ $t('page.products.sale_price_label') }}</span><p class="font-medium text-text-primary">{{ formatXOF(product.price_xof) }}</p></div>
            <div><span class="text-text-tertiary">{{ $t('page.products.purchase_price_label') }}</span><p class="text-text-primary">{{ product.purchase_price_xof ? formatXOF(product.purchase_price_xof) : '—' }}</p></div>
            <div><span class="text-text-tertiary">{{ $t('page.products.cost_price_label') }}</span><p class="text-text-primary">{{ product.cost_price_xof ? formatXOF(product.cost_price_xof) : '—' }}</p></div>
            <div><span class="text-text-tertiary">{{ $t('page.products.wholesale_price_label') }}</span><p class="text-text-primary">{{ product.wholesale_price_xof ? formatXOF(product.wholesale_price_xof) : '—' }}</p></div>
            <div>
              <span class="text-text-tertiary">{{ $t('page.products.status_label') }}</span>
              <BaseBadge :variant="product.is_active ? 'success' : 'default'" size="sm" class="ml-2">
                {{ product.is_active ? $t('status.active') : $t('status.inactive') }}
              </BaseBadge>
            </div>
          </div>
        </BaseCard>

        <BaseCard :title="$t('page.products.stock_by_warehouse')" padding="lg">
          <div v-if="stocks.length === 0" class="text-sm text-text-tertiary">{{ $t('page.products.no_stock') }}</div>
          <table v-else class="min-w-full divide-y divide-border">
            <thead class="bg-surface-secondary">
              <tr>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">{{ $t('form.warehouse') }}</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">{{ $t('common.quantity') }}</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">{{ $t('form.min_stock') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="s in stocks" :key="s.warehouse?.id || s.id" class="hover:bg-surface-secondary">
                <td class="px-4 py-3 text-sm text-text-primary">{{ s.warehouse?.name || $t('page.products.main_warehouse') }}</td>
                <td class="px-4 py-3 text-sm text-right text-text-primary">{{ s.quantity ?? s.stock }}</td>
                <td class="px-4 py-3 text-sm text-right text-text-tertiary">{{ s.min_stock ?? product.min_stock }}</td>
              </tr>
            </tbody>
          </table>
        </BaseCard>

        <BaseCard :title="$t('page.products.stock_movements')" padding="lg">
          <div v-if="movements.length === 0" class="text-sm text-text-tertiary">{{ $t('page.products.no_movements') }}</div>
          <table v-else class="min-w-full divide-y divide-border">
            <thead class="bg-surface-secondary">
              <tr>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">{{ $t('common.date') }}</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-text-tertiary uppercase">{{ $t('common.type') }}</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">{{ $t('common.quantity') }}</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">{{ $t('common.reference') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="m in movements" :key="m.id" class="hover:bg-surface-secondary">
                <td class="px-4 py-3 text-sm text-text-tertiary">{{ m.created_at || m.date }}</td>
                <td class="px-4 py-3 text-center">
                  <BaseBadge :variant="m.type === 'in' ? 'success' : 'danger'" size="xs">
                    {{ m.type === 'in' ? $t('status.entry') : $t('status.exit') }}
                  </BaseBadge>
                </td>
                <td class="px-4 py-3 text-sm text-right text-text-primary">{{ m.quantity }}</td>
                <td class="px-4 py-3 text-sm text-text-tertiary">{{ m.reason || m.reference || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </BaseCard>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue';
import { PencilIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const route = useRoute();
const product = ref(null);
const loading = ref(true);
const error = ref('');
const stocks = ref([]);
const movements = ref([]);

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

async function fetchProduct() {
  loading.value = true;
  try {
    const { data } = await axios.get(`/products/${route.params.id}`);
    product.value = data.data ?? data;

    if (product.value.stocks) stocks.value = product.value.stocks;
    else if (product.value.warehouses) stocks.value = product.value.warehouses;
    else stocks.value = [{ warehouse: null, quantity: product.value.quantity, min_stock: product.value.min_stock }];

    if (product.value.stock_movements) movements.value = product.value.stock_movements;
  } catch {
    error.value = t('page.products.load_error');
  } finally {
    loading.value = false;
  }
}

onMounted(fetchProduct);
</script>
