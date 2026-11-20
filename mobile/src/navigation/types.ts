import type { NavigatorScreenParams } from '@react-navigation/native';
import type { ID } from '../types';

export type AuthStackParamList = {
  Login: undefined;
  Register: undefined;
};

export type MainTabParamList = {
  Home: undefined;
  Tasks: undefined;
  Campaigns: undefined;
  Wallet: undefined;
  Profile: undefined;
};

export type RootStackParamList = {
  Auth: NavigatorScreenParams<AuthStackParamList>;
  Main: NavigatorScreenParams<MainTabParamList>;
  Earnings: undefined;
  Notifications: undefined;
  Announcements: undefined;
  PayoutAccounts: undefined;
  Withdraw: undefined;
  Deposit: undefined;
  EditProfile: undefined;
  TaskRun: {
    id: ID;
    mode: 'task' | 'campaign';
    title: string;
    embedUrl?: string;
    targetUrl?: string;
    reward?: number;
  };
};
