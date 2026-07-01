export function useMoneyFormat() {
  const formatMoney = (kopiyky: number, currency: string): string => {
    const amount = kopiyky / 100;
    return new Intl.NumberFormat('uk-UA', {
      style: 'currency',
      currency,
      minimumFractionDigits: 2,
    }).format(amount);
  };

  return { formatMoney };
}
