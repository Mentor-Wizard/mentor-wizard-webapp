<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import AppPagination from '@/Components/Navigation/AppPagination.vue';
import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import { useMoneyFormat } from '@/Composables/useMoneyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
  Currency,
  Payable,
  PayableType,
  PaymentStatus,
} from '@/types/payments';

interface PaymentRow {
  id: number;
  order_reference: string;
  amount: number;
  currency: Currency;
  transaction_status: PaymentStatus | null;
  fee_amount: number | null;
  fee_percentage: number | null;
  net_amount: number | null;
  payment_system: string | null;
  refunded_at: string | null;
  refund_amount: number | null;
  created_at: string;
  payable: Payable | null;
}

interface PaginatorLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginatorMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

interface Paginator {
  data: PaymentRow[];
  links: PaginatorLink[];
  meta: PaginatorMeta;
}

interface Filters {
  status: PaymentStatus | null;
  from: string | null;
  to: string | null;
}

interface Props {
  payments: Paginator;
  filters: Filters;
}

const props = defineProps<Props>();

defineOptions({ name: 'PaymentHistory' });

const { formatMoney } = useMoneyFormat();

const page = usePage();

const notification = ref<{ show: boolean; success: boolean; message: string }>({
  show: false,
  success: false,
  message: '',
});

const showNotification = (success: boolean, message: string) => {
  notification.value = { show: true, success, message };
  setTimeout(() => {
    notification.value.show = false;
  }, 5000);
};

onMounted(() => {
  const flash = page.props.flash as
    | { success?: string; error?: string }
    | undefined;
  if (flash?.success) {
    showNotification(true, flash.success);
  } else if (flash?.error) {
    showNotification(false, flash.error);
  }
});

watch(
  () => page.props.flash,
  (flash) => {
    const f = flash as { success?: string; error?: string } | undefined;
    if (f?.success) showNotification(true, f.success);
    else if (f?.error) showNotification(false, f.error);
  },
);

const form = useForm({
  status: props.filters.status ?? '',
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
});

const applyFilters = () => {
  form.get(route('payments.history'), { preserveState: true });
};

const resetFilters = () => {
  form.status = '';
  form.from = '';
  form.to = '';
  form.get(route('payments.history'), { preserveState: true });
};

const paginatorData = computed(() => ({
  links: props.payments.links,
  prev_page_url: props.payments.links[0]?.url ?? null,
  next_page_url: props.payments.links.at(-1)?.url ?? null,
}));

const statusBadgeClass = (status: PaymentStatus | null): string => {
  switch (status) {
    case 'approved':
      return 'bg-green-100 text-green-800';
    case 'declined':
    case 'expired':
      return 'bg-red-100 text-red-800';
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'refunded':
      return 'bg-gray-100 text-gray-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const statusLabel: Record<string, string> = {
  approved: 'Підтверджено',
  declined: 'Відхилено',
  expired: 'Термін вичерпано',
  pending: 'Очікується',
  refunded: 'Повернено',
};

const payableTypeLabel = (type: PayableType): string =>
  type === 'MentorSession' ? 'Сесія' : 'Програма';

const formatDate = (iso: string | null): string => {
  if (!iso) return '—';
  return new Date(iso).toLocaleString('uk-UA');
};

const statusOptions: Array<{ value: string; label: string }> = [
  { value: '', label: 'Усі' },
  { value: 'approved', label: 'Підтверджено' },
  { value: 'pending', label: 'Очікується' },
  { value: 'declined', label: 'Відхилено' },
  { value: 'expired', label: 'Термін вичерпано' },
  { value: 'refunded', label: 'Повернено' },
];
</script>

<template>
  <Head title="Історія платежів" />

  <AuthenticatedLayout>
    <div>
      <PopUp
        :show-status="notification.show"
        :success="notification.success"
        :message="notification.message"
      />
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Історія платежів</h1>
        <p class="mt-2 text-sm text-gray-600">
          Всього: {{ payments.meta.total }} платежів
        </p>
      </div>

      <div
        class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
      >
        <form
          class="grid grid-cols-1 gap-4 sm:grid-cols-4"
          @submit.prevent="applyFilters"
        >
          <div>
            <label
              for="filter-status"
              class="block text-sm font-medium text-gray-700"
            >
              Статус
            </label>
            <select
              id="filter-status"
              v-model="form.status"
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            >
              <option
                v-for="option in statusOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <div>
            <label
              for="filter-from"
              class="block text-sm font-medium text-gray-700"
            >
              З дати
            </label>
            <input
              id="filter-from"
              v-model="form.from"
              type="date"
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div>
            <label
              for="filter-to"
              class="block text-sm font-medium text-gray-700"
            >
              По дату
            </label>
            <input
              id="filter-to"
              v-model="form.to"
              type="date"
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div class="flex items-end gap-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
            >
              Фільтрувати
            </button>
            <button
              type="button"
              :disabled="form.processing"
              class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 disabled:opacity-50"
              @click="resetFilters"
            >
              Скинути
            </button>
          </div>
        </form>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div
          v-if="payments.data.length === 0"
          class="py-16 text-center text-sm text-gray-500"
        >
          Платежів не знайдено.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                >
                  Замовлення
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                >
                  Послуга
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                >
                  Сума
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                >
                  Статус
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                >
                  Дата
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr
                v-for="payment in payments.data"
                :key="payment.id"
                class="hover:bg-gray-50"
              >
                <td class="px-4 py-3">
                  <span class="font-mono text-xs text-gray-900">
                    {{ payment.order_reference }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div v-if="payment.payable">
                    <span class="text-xs text-gray-500">{{
                      payableTypeLabel(payment.payable.type)
                    }}</span>
                    <p class="text-sm text-gray-900">
                      {{ payment.payable.label }}
                    </p>
                  </div>
                  <span v-else class="text-sm text-gray-400">—</span>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm font-semibold text-gray-900">
                    {{ formatMoney(payment.amount, payment.currency) }}
                  </p>
                  <p
                    v-if="payment.net_amount !== null"
                    class="text-xs text-gray-500"
                  >
                    Чисто:
                    {{ formatMoney(payment.net_amount, payment.currency) }}
                  </p>
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                      statusBadgeClass(payment.transaction_status),
                    ]"
                  >
                    {{
                      payment.transaction_status ?
                        (statusLabel[payment.transaction_status]
                        ?? payment.transaction_status)
                      : '—'
                    }}
                  </span>
                  <p
                    v-if="payment.refunded_at"
                    class="mt-1 text-xs text-gray-400"
                  >
                    Повернено: {{ formatDate(payment.refunded_at) }}
                  </p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500">
                  {{ formatDate(payment.created_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="payments.data.length > 0" class="px-4">
          <AppPagination :data="paginatorData" />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
