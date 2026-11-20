export const API_BASE_URL = 'https://reapersventory.com/api/v1';

export const STORAGE_KEYS = {
  token: 'reapersventory.auth.token',
  user: 'reapersventory.auth.user',
} as const;

export const DEVICE_NAME = 'reapersventory-mobile';

export const WITHDRAWAL_RULES = {
  minimumGross: 50,
  maximumGross: 250,
  feeRate: 0.1,
  cooldownHours: 24,
  feeLabel: 'Tax - fee',
} as const;
