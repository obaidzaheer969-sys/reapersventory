import type { ID } from '../types';

export type JsonRecord = Record<string, unknown>;

export function isRecord(value: unknown): value is JsonRecord {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function unwrap(payload: unknown, keys: string[]): unknown {
  let current = payload;

  for (let depth = 0; depth < 3; depth += 1) {
    if (Array.isArray(current)) {
      return current;
    }

    if (!isRecord(current)) {
      break;
    }

    const record = current;
    const nestedKey = keys.find(
      key => isRecord(record[key]) || Array.isArray(record[key]),
    );

    if (!nestedKey) {
      break;
    }

    current = current[nestedKey];
  }

  return current;
}

export function asObject(payload: unknown, keys: string[] = []): JsonRecord {
  const value = unwrap(payload, keys);

  return isRecord(value) ? value : {};
}

export function asList(payload: unknown, keys: string[] = []): unknown[] {
  const value = unwrap(payload, [...keys, 'data', 'items', 'results']);

  return Array.isArray(value) ? value : [];
}

export function text(value: unknown, fallback = ''): string {
  if (typeof value === 'string') {
    return value.trim() === '' ? fallback : value;
  }

  if (typeof value === 'number' && Number.isFinite(value)) {
    return String(value);
  }

  return fallback;
}

export function numberValue(value: unknown, fallback = 0): number {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === 'string') {
    const cleaned = value.replace(/,/g, '').trim();

    if (cleaned !== '') {
      const parsed = Number(cleaned);

      if (Number.isFinite(parsed)) {
        return parsed;
      }
    }
  }

  return fallback;
}

export function booleanValue(value: unknown, fallback = false): boolean {
  if (typeof value === 'boolean') {
    return value;
  }

  if (value === 1 || value === '1' || value === 'true') {
    return true;
  }

  if (value === 0 || value === '0' || value === 'false') {
    return false;
  }

  return fallback;
}

export function idValue(value: unknown): ID | null {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === 'string' && value.trim() !== '') {
    return value;
  }

  return null;
}

export function nullableText(value: unknown): string | null {
  return typeof value === 'string' && value.trim() !== '' ? value : null;
}

export function compact<T>(values: Array<T | null>): T[] {
  return values.filter((value): value is T => value !== null);
}
