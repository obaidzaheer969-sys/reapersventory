import { apiClient } from './client';
import {
  asList,
  asObject,
  booleanValue,
  compact,
  idValue,
  nullableText,
  numberValue,
  text,
} from './parse';
import type {
  Announcement,
  AuthResponse,
  AuthUser,
  Campaign,
  DashboardData,
  DepositPayload,
  ID,
  LoginPayload,
  NotificationItem,
  PayoutAccount,
  PayoutAccountPayload,
  PayoutRequest,
  PaymentMethod,
  ProfilePayload,
  RegisterPayload,
  Task,
  TaskProgress,
  Transaction,
  WalletData,
  WithdrawalPayload,
} from '../types';

type AttemptPayload = {
  playing?: boolean;
  opened?: boolean;
};

function pathId(id: ID): string {
  return encodeURIComponent(String(id));
}

function parseUser(payload: unknown): AuthUser | null {
  const record = asObject(payload, ['user']);
  const id = idValue(record.id);

  if (id === null) {
    return null;
  }

  return {
    id,
    name: text(record.name, text(record.first_name, 'Earner')),
    email: text(record.email),
    role: text(record.role, 'earner'),
    wallet_balance: numberValue(record.wallet_balance),
    is_active: booleanValue(record.is_active, true),
  };
}

function requireUser(payload: unknown): AuthUser {
  const user = parseUser(payload);

  if (!user) {
    throw new Error('The server returned an invalid account response.');
  }

  return user;
}

function parseAuth(payload: unknown): AuthResponse {
  const record = asObject(payload);
  const token = text(record.token);

  if (token === '') {
    throw new Error('The server returned an invalid sign-in response.');
  }

  return {
    token,
    token_type: text(record.token_type, 'Bearer'),
    user: requireUser(record.user ?? payload),
  };
}

function parseTransaction(payload: unknown): Transaction | null {
  const record = asObject(payload);
  const id = idValue(record.id);

  if (id === null) {
    return null;
  }

  const amount = numberValue(record.amount);
  const fee = numberValue(record.fee);

  return {
    id,
    type: text(record.type, 'transaction'),
    amount,
    fee,
    net_amount: numberValue(record.net_amount, amount - fee),
    description: text(record.description),
    created_at: text(record.created_at),
  };
}

function parseAnnouncement(payload: unknown): Announcement | null {
  const record = asObject(payload);
  const id = idValue(record.id);

  if (id === null) {
    return null;
  }

  return {
    id,
    title: text(record.title, 'Announcement'),
    body: text(record.body),
    created_at: text(record.created_at),
  };
}

function parseNotification(payload: unknown): NotificationItem | null {
  const record = asObject(payload);
  const id = idValue(record.id);

  if (id === null) {
    return null;
  }

  return {
    id,
    title: text(record.title, 'Notification'),
    body: text(record.body),
    read_at: nullableText(record.read_at),
    created_at: text(record.created_at),
  };
}

function parseTask(payload: unknown): Task | null {
  const record = asObject(payload);
  const id = idValue(record.id);

  if (id === null) {
    return null;
  }

  return {
    id,
    title: text(record.title, 'Task'),
    description: text(record.description),
    type: text(record.type, 'task'),
    reward: numberValue(record.reward),
    duration_seconds: numberValue(record.duration_seconds),
    status: text(record.status, 'available'),
    remaining_seconds: numberValue(record.remaining_seconds),
    can_start: booleanValue(record.can_start),
    proof_required: booleanValue(record.proof_required),
    url: text(record.url),
    created_at: text(record.created_at),
    embedded: booleanValue(record.embedded),
    embed_url: nullableText(record.embed_url),
    platform: nullableText(record.platform),
    opened: booleanValue(record.opened),
    watched_seconds: numberValue(record.watched_seconds),
    timer_paused: booleanValue(record.timer_paused),
    can_claim: booleanValue(record.can_claim),
  };
}

function parseCampaign(payload: unknown): Campaign | null {
  const record = asObject(payload);
  const id = idValue(record.id);

  if (id === null) {
    return null;
  }

  return {
    id,
    title: text(record.title, 'Campaign'),
    description: text(record.description),
    reward: numberValue(record.reward),
    admin_fee: numberValue(record.admin_fee),
    total_actions: numberValue(record.total_actions),
    completed_actions: numberValue(record.completed_actions),
    status: text(record.status, 'active'),
    created_at: text(record.created_at),
    embedded: booleanValue(record.embedded),
    embed_url: nullableText(record.embed_url),
    platform: nullableText(record.platform),
    opened: booleanValue(record.opened),
    watched_seconds: numberValue(record.watched_seconds),
    timer_paused: booleanValue(record.timer_paused),
    can_claim: booleanValue(record.can_claim),
  };
}

function parseProgress(payload: unknown): TaskProgress {
  const record = asObject(payload, ['data']);

  return {
    ok: booleanValue(record.ok, true),
    watched_seconds: numberValue(record.watched_seconds),
    required_seconds: numberValue(record.required_seconds),
    remaining_watch_seconds: numberValue(record.remaining_watch_seconds),
    timer_remaining_seconds: numberValue(record.timer_remaining_seconds),
    timer_paused: booleanValue(record.timer_paused),
    can_claim: booleanValue(record.can_claim),
  };
}

function parsePayoutAccount(payload: unknown): PayoutAccount | null {
  const record = asObject(payload);
  const id = idValue(record.id);

  if (id === null) {
    return null;
  }

  const provider = text(record.provider);

  return {
    id,
    provider,
    provider_label: text(record.provider_label, provider),
    account_name: text(record.account_name),
    account_number: text(record.account_number),
    is_default: booleanValue(record.is_default),
  };
}

function parsePaymentMethod(payload: unknown): PaymentMethod | null {
  const record = asObject(payload);
  const id = idValue(record.id);

  if (id === null) {
    return null;
  }

  return {
    id,
    name: text(record.name, 'Payment method'),
    code: text(record.code),
    instructions: text(record.instructions),
    account_name: text(record.account_name),
    account_number: text(record.account_number),
    additional_info: text(record.additional_info),
  };
}

function parsePayoutRequest(payload: unknown): PayoutRequest | null {
  const record = asObject(payload);
  const id = idValue(record.id);

  if (id === null) {
    return null;
  }

  const amount = numberValue(record.amount);
  const fee = numberValue(record.fee);

  return {
    id,
    amount,
    fee,
    net_amount: numberValue(record.net_amount, amount - fee),
    status: text(record.status, 'pending'),
    provider: text(record.provider),
    account_number: text(record.account_number),
    created_at: text(record.created_at),
  };
}

function parseDashboard(payload: unknown): DashboardData {
  const record = asObject(payload, ['dashboard']);

  return {
    wallet_balance: numberValue(record.wallet_balance),
    today_earned: numberValue(record.today_earned),
    total_earned: numberValue(record.total_earned),
    available_tasks: numberValue(record.available_tasks),
    active_campaigns: numberValue(record.active_campaigns),
    pending_withdrawals: numberValue(record.pending_withdrawals),
    announcements: compact(
      asList(record.announcements).map(parseAnnouncement),
    ),
    recent_transactions: compact(
      asList(record.recent_transactions ?? record.transactions).map(
        parseTransaction,
      ),
    ),
  };
}

function parseWallet(payload: unknown): WalletData {
  const record = asObject(payload, ['wallet']);

  return {
    wallet_balance: numberValue(record.wallet_balance, numberValue(record.balance)),
    total_earned: numberValue(record.total_earned),
    pending_withdrawals: numberValue(record.pending_withdrawals),
    transactions: compact(
      asList(record.transactions ?? record.recent_transactions).map(
        parseTransaction,
      ),
    ),
  };
}

export const endpoints = {
  async register(payload: RegisterPayload): Promise<AuthResponse> {
    const response = await apiClient.post('/register', payload);

    return parseAuth(response.data);
  },

  async login(payload: LoginPayload): Promise<AuthResponse> {
    const response = await apiClient.post('/login', payload);

    return parseAuth(response.data);
  },

  async logout(): Promise<void> {
    await apiClient.post('/logout');
  },

  async getUser(): Promise<AuthUser> {
    const response = await apiClient.get('/user');

    return requireUser(response.data);
  },

  async updateProfile(payload: ProfilePayload): Promise<AuthUser | null> {
    const response = await apiClient.put('/profile', payload);

    return parseUser(response.data);
  },

  async getDashboard(): Promise<DashboardData> {
    const response = await apiClient.get('/dashboard');

    return parseDashboard(response.data);
  },

  async getTasks(): Promise<Task[]> {
    const response = await apiClient.get('/tasks');

    return compact(asList(response.data, ['tasks']).map(parseTask));
  },

  async startTask(id: ID, payload?: AttemptPayload): Promise<void> {
    const url = `/tasks/${pathId(id)}/start`;

    if (payload) {
      await apiClient.post(url, payload);
      return;
    }

    await apiClient.post(url);
  },

  async completeTask(id: ID, payload?: AttemptPayload): Promise<void> {
    const url = `/tasks/${pathId(id)}/complete`;

    if (payload) {
      await apiClient.post(url, payload);
      return;
    }

    await apiClient.post(url);
  },

  async markTaskOpened(id: ID): Promise<void> {
    await apiClient.post(`/tasks/${pathId(id)}/opened`);
  },

  async sendTaskProgress(id: ID, playing: boolean): Promise<TaskProgress> {
    const response = await apiClient.post(`/tasks/${pathId(id)}/progress`, {
      playing,
    });

    return parseProgress(response.data);
  },

  async getCampaigns(): Promise<Campaign[]> {
    const response = await apiClient.get('/campaigns');

    return compact(asList(response.data, ['campaigns']).map(parseCampaign));
  },

  async startCampaign(id: ID, payload?: AttemptPayload): Promise<void> {
    const url = `/campaigns/${pathId(id)}/start`;

    if (payload) {
      await apiClient.post(url, payload);
      return;
    }

    await apiClient.post(url);
  },

  async completeCampaign(id: ID, payload?: AttemptPayload): Promise<void> {
    const url = `/campaigns/${pathId(id)}/complete`;

    if (payload) {
      await apiClient.post(url, payload);
      return;
    }

    await apiClient.post(url);
  },

  async markCampaignOpened(id: ID): Promise<void> {
    await apiClient.post(`/campaigns/${pathId(id)}/opened`);
  },

  async sendCampaignProgress(
    id: ID,
    playing: boolean,
  ): Promise<TaskProgress> {
    const response = await apiClient.post(
      `/campaigns/${pathId(id)}/progress`,
      { playing },
    );

    return parseProgress(response.data);
  },

  async getEarnings(): Promise<Transaction[]> {
    const response = await apiClient.get('/earnings');

    return compact(
      asList(response.data, ['earnings', 'transactions']).map(parseTransaction),
    );
  },

  async getWallet(): Promise<WalletData> {
    const response = await apiClient.get('/wallet');

    return parseWallet(response.data);
  },

  async getPaymentMethods(): Promise<PaymentMethod[]> {
    const response = await apiClient.get('/payment-methods');

    return compact(
      asList(response.data, ['payment_methods']).map(parsePaymentMethod),
    );
  },

  async createDeposit(payload: DepositPayload): Promise<void> {
    await apiClient.post('/deposits', payload);
  },

  async getPayoutAccounts(): Promise<PayoutAccount[]> {
    const response = await apiClient.get('/payout-accounts');

    return compact(
      asList(response.data, ['payout_accounts']).map(parsePayoutAccount),
    );
  },

  async createPayoutAccount(payload: PayoutAccountPayload): Promise<void> {
    await apiClient.post('/payout-accounts', payload);
  },

  async getWithdrawals(): Promise<PayoutRequest[]> {
    const response = await apiClient.get('/withdrawals');

    return compact(
      asList(response.data, ['withdrawals', 'requests']).map(parsePayoutRequest),
    );
  },

  async createWithdrawal(payload: WithdrawalPayload): Promise<void> {
    await apiClient.post('/withdrawals', payload);
  },

  async getAnnouncements(): Promise<Announcement[]> {
    const response = await apiClient.get('/announcements');

    return compact(
      asList(response.data, ['announcements']).map(parseAnnouncement),
    );
  },

  async getNotifications(): Promise<NotificationItem[]> {
    const response = await apiClient.get('/notifications');

    return compact(
      asList(response.data, ['notifications']).map(parseNotification),
    );
  },

  async markNotificationRead(id: ID): Promise<void> {
    await apiClient.post(`/notifications/${pathId(id)}/read`);
  },
};
