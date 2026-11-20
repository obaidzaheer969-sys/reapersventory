import AsyncStorage from '@react-native-async-storage/async-storage';
import axios, { type AxiosError } from 'axios';
import { API_BASE_URL, STORAGE_KEYS } from '../config';
import { isRecord, text } from './parse';

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

let unauthorizedHandler: (() => void) | null = null;

export function setUnauthorizedHandler(handler: (() => void) | null): void {
  unauthorizedHandler = handler;
}

apiClient.interceptors.request.use(async config => {
  const token = await AsyncStorage.getItem(STORAGE_KEYS.token);

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

apiClient.interceptors.response.use(
  response => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      unauthorizedHandler?.();
    }

    return Promise.reject(error);
  },
);

export function getErrorMessage(
  error: unknown,
  fallback = 'Something went wrong. Please try again.',
): string {
  if (axios.isAxiosError(error)) {
    const data: unknown = error.response?.data;

    if (typeof data === 'string' && data.trim() !== '') {
      return data;
    }

    if (isRecord(data)) {
      const message = text(data.message);

      if (message !== '') {
        return message;
      }

      const errors = data.errors;

      if (isRecord(errors)) {
        const messages = Object.values(errors)
          .map(value => {
            if (Array.isArray(value)) {
              return value.map(item => text(item)).filter(Boolean).join(', ');
            }

            return text(value);
          })
          .filter(Boolean);

        if (messages.length > 0) {
          return messages.join(' ');
        }
      }
    }
  }

  if (error instanceof Error && error.message !== '') {
    return error.message;
  }

  return fallback;
}
