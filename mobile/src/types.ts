export type ID = number | string;

export interface AuthUser {
  id: ID;
  name: string;
  email: string;
  role: string;
  wallet_balance: number;
  is_active: boolean;
}

export interface AuthResponse {
  token: string;
  token_type: string;
  user: AuthUser;
}

export interface LoginPayload {
  email: string;
  password: string;
  device_name: string;
}

export interface RegisterPayload {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
  password_confirmation: string;
  terms: boolean;
  device_name: string;
}

export interface ProfilePayload {
  name: string;
  email: string;
  current_password?: string;
  password?: string;
  password_confirmation?: string;
}

export interface Announcement {
  id: ID;
  title: string;
  body: string;
  created_at: string;
}

export interface Transaction {
  id: ID;
  type: string;
  amount: number;
  fee: number;
  net_amount: number;
  description: string;
  created_at: string;
}

export interface DashboardData {
  wallet_balance: number;
  today_earned: number;
  total_earned: number;
  available_tasks: number;
  active_campaigns: number;
  pending_withdrawals: number;
  announcements: Announcement[];
  recent_transactions: Transaction[];
}

export interface Task {
  id: ID;
  title: string;
  description: string;
  type: string;
  reward: number;
  duration_seconds: number;
  status: string;
  remaining_seconds: number;
  can_start: boolean;
  proof_required: boolean;
  url: string;
  created_at: string;
  embedded: boolean;
  embed_url: string | null;
  platform: string | null;
  opened: boolean;
  watched_seconds: number;
  timer_paused: boolean;
  can_claim: boolean;
}

export interface Campaign {
  id: ID;
  title: string;
  description: string;
  reward: number;
  admin_fee: number;
  total_actions: number;
  completed_actions: number;
  status: string;
  created_at: string;
  embedded: boolean;
  embed_url: string | null;
  platform: string | null;
  opened: boolean;
  watched_seconds: number;
  timer_paused: boolean;
  can_claim: boolean;
}

export interface TaskProgress {
  ok: boolean;
  watched_seconds: number;
  required_seconds: number;
  remaining_watch_seconds: number;
  timer_remaining_seconds: number;
  timer_paused: boolean;
  can_claim: boolean;
}

export interface WalletData {
  wallet_balance: number;
  total_earned: number;
  pending_withdrawals: number;
  transactions: Transaction[];
}

export interface NotificationItem {
  id: ID;
  title: string;
  body: string;
  read_at: string | null;
  created_at: string;
}

export interface PayoutAccount {
  id: ID;
  provider: string;
  provider_label: string;
  account_name: string;
  account_number: string;
  is_default: boolean;
}

export interface PayoutAccountPayload {
  provider: string;
  account_name: string;
  account_number: string;
  is_default?: boolean;
}

export interface PaymentMethod {
  id: ID;
  name: string;
  code: string;
  instructions: string;
  account_name: string;
  account_number: string;
  additional_info: string;
}

export interface DepositPayload {
  payment_method_id: ID;
  amount: number;
  transaction_id?: string;
  note?: string;
}

export interface PayoutRequest {
  id: ID;
  amount: number;
  fee: number;
  net_amount: number;
  status: string;
  provider: string;
  account_number: string;
  created_at: string;
}

export interface WithdrawalPayload {
  amount: number;
  payout_account_id: ID;
}
