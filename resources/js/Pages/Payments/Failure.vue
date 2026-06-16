<script setup lang="ts">
import { XCircleIcon } from '@heroicons/vue/24/outline';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import { useMoneyFormat } from '@/Composables/useMoneyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Currency, Payable, PaymentStatus } from '@/types/payments';

interface PaymentFailure {
  order_reference: string;
  amount: number;
  currency: Currency;
  transaction_status: PaymentStatus | null;
  reason: string | null;
  reason_code: string | null;
  created_at: string;
}

interface Props {
  payment: PaymentFailure | null;
  payable: Payable | null;
  retryUrl: string | null;
}

const props = defineProps<Props>();

defineOptions({ name: 'PaymentFailure' });

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
  if (flash?.error) {
    showNotification(false, flash.error);
  } else if (flash?.success) {
    showNotification(true, flash.success);
  }
});

const formattedDate = computed(() => {
  if (!props.payment?.created_at) return null;
  return new Date(props.payment.created_at).toLocaleString('uk-UA');
});

const payableTypeLabel = computed(() => {
  if (!props.payable) return null;
  return props.payable.type === 'MentorSession' ? 'Сесія' : 'Програма';
});

const statusLabel: Record<string, string> = {
  declined: 'Відхилено',
  expired: 'Термін дії вичерпано',
  pending: 'Очікується',
  approved: 'Підтверджено',
  refunded: 'Повернено',
};

const payableTypeSnake = computed<string | null>(() => {
  if (!props.payable) return null;
  return props.payable.type === 'MentorSession' ?
      'mentor_session'
    : 'mentor_program';
});

const retryProcessing = ref(false);

const retry = async () => {
  if (!props.retryUrl || !props.payable || !payableTypeSnake.value) return;

  retryProcessing.value = true;
  try {
    const response = await axios.post<string>(props.retryUrl, {
      payable_type: payableTypeSnake.value,
      payable_id: props.payable.id,
    });
    const blob = new Blob([response.data], { type: 'text/html' });
    const blobUrl = URL.createObjectURL(blob);
    window.location.assign(blobUrl);
  } catch {
    showNotification(
      false,
      'Не вдалося ініціювати повторний платіж. Спробуйте ще раз.',
    );
    retryProcessing.value = false;
  }
};
</script>

<template>
  <Head title="Помилка оплати" />

  <AuthenticatedLayout>
    <div>
      <PopUp
        :show-status="notification.show"
        :success="notification.success"
        :message="notification.message"
      />
    </div>

    <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
      <div class="rounded-lg border border-red-200 bg-white shadow-sm">
        <div class="p-8 text-center">
          <XCircleIcon
            class="mx-auto h-16 w-16 text-red-500"
            aria-hidden="true"
          />
          <h1 class="mt-4 text-2xl font-bold text-gray-900">Помилка оплати</h1>
          <p class="mt-2 text-sm text-gray-600">
            На жаль, платіж не вдалося здійснити.
          </p>
        </div>

        <div v-if="payment" class="border-t border-gray-200 px-8 py-6">
          <dl class="space-y-4">
            <div class="flex justify-between">
              <dt class="text-sm font-medium text-gray-500">
                Номер замовлення
              </dt>
              <dd class="font-mono text-sm text-gray-900">
                {{ payment.order_reference }}
              </dd>
            </div>

            <div v-if="payable" class="flex justify-between">
              <dt class="text-sm font-medium text-gray-500">
                {{ payableTypeLabel }}
              </dt>
              <dd class="text-sm text-gray-900">{{ payable.label }}</dd>
            </div>

            <div class="flex justify-between">
              <dt class="text-sm font-medium text-gray-500">Сума</dt>
              <dd class="text-sm font-semibold text-gray-900">
                {{ formatMoney(payment.amount, payment.currency) }}
              </dd>
            </div>

            <div v-if="payment.transaction_status" class="flex justify-between">
              <dt class="text-sm font-medium text-gray-500">Статус</dt>
              <dd class="text-sm text-red-700">
                {{
                  statusLabel[payment.transaction_status]
                  ?? payment.transaction_status
                }}
              </dd>
            </div>

            <div v-if="payment.reason" class="flex justify-between">
              <dt class="text-sm font-medium text-gray-500">Причина</dt>
              <dd class="text-sm text-gray-900">{{ payment.reason }}</dd>
            </div>

            <div v-if="payment.reason_code" class="flex justify-between">
              <dt class="text-sm font-medium text-gray-500">Код причини</dt>
              <dd class="font-mono text-sm text-gray-900">
                {{ payment.reason_code }}
              </dd>
            </div>

            <div v-if="formattedDate" class="flex justify-between">
              <dt class="text-sm font-medium text-gray-500">Дата</dt>
              <dd class="text-sm text-gray-900">{{ formattedDate }}</dd>
            </div>
          </dl>
        </div>

        <div v-else class="border-t border-gray-200 px-8 py-6">
          <p class="text-center text-sm text-gray-500">
            Інформацію про платіж не знайдено.
          </p>
        </div>

        <div class="space-y-3 border-t border-gray-200 px-8 py-6">
          <button
            v-if="retryUrl && payable"
            type="button"
            :disabled="retryProcessing"
            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
            @click="retry"
          >
            {{ retryProcessing ? 'Перенаправлення...' : 'Спробувати ще раз' }}
          </button>

          <Link
            :href="route('pages.calendar.pending')"
            class="inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
          >
            Повернутись до бронювань
          </Link>

          <Link
            :href="route('pages.calendar.confirmed')"
            class="inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-500 shadow-sm ring-1 ring-gray-200 ring-inset hover:bg-gray-50"
          >
            Підтверджені сесії
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
