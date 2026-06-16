<script setup lang="ts">
import { CheckCircleIcon } from '@heroicons/vue/24/outline';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import { useMoneyFormat } from '@/Composables/useMoneyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Currency, Payable, PaymentStatus } from '@/types/payments';

interface PaymentSuccess {
  order_reference: string;
  amount: number;
  currency: Currency;
  transaction_status: PaymentStatus | null;
  payment_system: string | null;
  created_at: string;
}

interface Props {
  payment: PaymentSuccess | null;
  payable: Payable | null;
}

const props = defineProps<Props>();

defineOptions({ name: 'PaymentSuccess' });

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

const formattedDate = computed(() => {
  if (!props.payment?.created_at) return null;
  return new Date(props.payment.created_at).toLocaleString('uk-UA');
});

const payableTypeLabel = computed(() => {
  if (!props.payable) return null;
  return props.payable.type === 'MentorSession' ? 'Сесія' : 'Програма';
});
</script>

<template>
  <Head title="Оплата успішна" />

  <AuthenticatedLayout>
    <div>
      <PopUp
        :show-status="notification.show"
        :success="notification.success"
        :message="notification.message"
      />
    </div>

    <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
      <div class="rounded-lg border border-green-200 bg-white shadow-sm">
        <div class="p-8 text-center">
          <CheckCircleIcon
            class="mx-auto h-16 w-16 text-green-500"
            aria-hidden="true"
          />
          <h1 class="mt-4 text-2xl font-bold text-gray-900">
            Оплату успішно здійснено
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            Дякуємо! Ваш платіж оброблено.
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

            <div v-if="payment.payment_system" class="flex justify-between">
              <dt class="text-sm font-medium text-gray-500">
                Платіжна система
              </dt>
              <dd class="text-sm text-gray-900">
                {{ payment.payment_system }}
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

        <div class="border-t border-gray-200 px-8 py-6">
          <Link
            :href="route('pages.calendar.confirmed')"
            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Перейти до сесій
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
