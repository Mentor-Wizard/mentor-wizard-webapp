export type PayableType = 'MentorSession' | 'MentorProgram';

export type PaymentStatus =
  | 'pending'
  | 'approved'
  | 'declined'
  | 'refunded'
  | 'expired';

export type Currency = 'UAH' | 'USD' | 'EUR' | 'GBP';

export interface Payable {
  type: PayableType;
  id: number;
  label: string;
}
