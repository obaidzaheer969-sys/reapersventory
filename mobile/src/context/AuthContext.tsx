import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { setUnauthorizedHandler } from '../api/client';
import { endpoints } from '../api/endpoints';
import {
  booleanValue,
  idValue,
  isRecord,
  numberValue,
  text,
} from '../api/parse';
import { DEVICE_NAME, STORAGE_KEYS } from '../config';
import type { AuthUser, LoginPayload, RegisterPayload } from '../types';

type AuthContextValue = {
  user: AuthUser | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (input: Omit<RegisterPayload, 'device_name'>) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

function parseStoredUser(raw: string | null): AuthUser | null {
  if (!raw) {
    return null;
  }

  try {
    const parsed: unknown = JSON.parse(raw);

    if (!isRecord(parsed)) {
      return null;
    }

    const id = idValue(parsed.id);

    if (id === null) {
      return null;
    }

    return {
      id,
      name: text(parsed.name, 'Earner'),
      email: text(parsed.email),
      role: text(parsed.role, 'earner'),
      wallet_balance: numberValue(parsed.wallet_balance),
      is_active: booleanValue(parsed.is_active, true),
    };
  } catch {
    return null;
  }
}

async function clearSession(): Promise<void> {
  await Promise.all([
    AsyncStorage.removeItem(STORAGE_KEYS.token),
    AsyncStorage.removeItem(STORAGE_KEYS.user),
  ]);
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;

    setUnauthorizedHandler(() => {
      setUser(null);
      clearSession().catch(() => undefined);
    });

    const hydrate = async () => {
      try {
        const [storedToken, storedUser] = await Promise.all([
          AsyncStorage.getItem(STORAGE_KEYS.token),
          AsyncStorage.getItem(STORAGE_KEYS.user),
        ]);

        if (!isMounted || !storedToken) {
          return;
        }

        const localUser = parseStoredUser(storedUser);

        if (localUser && isMounted) {
          setUser(localUser);
        }

        const freshUser = await endpoints.getUser();

        if (isMounted) {
          setUser(freshUser);
          await AsyncStorage.setItem(
            STORAGE_KEYS.user,
            JSON.stringify(freshUser),
          );
        }
      } catch {
        // Keep the persisted account when the device is offline.
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    };

    hydrate().catch(() => undefined);

    return () => {
      isMounted = false;
      setUnauthorizedHandler(null);
    };
  }, []);

  const persistSession = useCallback(
    async (token: string, authenticatedUser: AuthUser) => {
      await Promise.all([
        AsyncStorage.setItem(STORAGE_KEYS.token, token),
        AsyncStorage.setItem(
          STORAGE_KEYS.user,
          JSON.stringify(authenticatedUser),
        ),
      ]);
      setUser(authenticatedUser);
    },
    [],
  );

  const login = useCallback(
    async (email: string, password: string) => {
      const payload: LoginPayload = { email, password, device_name: DEVICE_NAME };
      const response = await endpoints.login(payload);

      await persistSession(response.token, response.user);
    },
    [persistSession],
  );

  const register = useCallback(
    async (input: Omit<RegisterPayload, 'device_name'>) => {
      const payload: RegisterPayload = { ...input, device_name: DEVICE_NAME };
      const response = await endpoints.register(payload);

      await persistSession(response.token, response.user);
    },
    [persistSession],
  );

  const logout = useCallback(async () => {
    try {
      await endpoints.logout();
    } catch {
      // Always clear local session, even if the server is unreachable.
    } finally {
      await clearSession();
      setUser(null);
    }
  }, []);

  const refreshUser = useCallback(async () => {
    try {
      const freshUser = await endpoints.getUser();
      setUser(freshUser);
      await AsyncStorage.setItem(
        STORAGE_KEYS.user,
        JSON.stringify(freshUser),
      );
    } catch {
      // Keep the current account when the refresh fails.
    }
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      isLoading,
      isAuthenticated: user !== null,
      login,
      register,
      logout,
      refreshUser,
    }),
    [user, isLoading, login, register, logout, refreshUser],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error('useAuth must be used inside an AuthProvider.');
  }

  return context;
}
