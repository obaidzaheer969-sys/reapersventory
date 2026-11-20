import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import { Logo } from '../components/Logo';
import { useAuth } from '../context/AuthContext';
import { AnnouncementsScreen } from '../screens/AnnouncementsScreen';
import { DepositScreen } from '../screens/DepositScreen';
import { EarningsScreen } from '../screens/EarningsScreen';
import { EditProfileScreen } from '../screens/EditProfileScreen';
import { NotificationsScreen } from '../screens/NotificationsScreen';
import { PayoutAccountsScreen } from '../screens/PayoutAccountsScreen';
import { TaskRunScreen } from '../screens/TaskRunScreen';
import { WithdrawScreen } from '../screens/WithdrawScreen';
import { LoginScreen } from '../screens/auth/LoginScreen';
import { RegisterScreen } from '../screens/auth/RegisterScreen';
import { CampaignsScreen } from '../screens/main/CampaignsScreen';
import { HomeScreen } from '../screens/main/HomeScreen';
import { ProfileScreen } from '../screens/main/ProfileScreen';
import { TasksScreen } from '../screens/main/TasksScreen';
import { WalletScreen } from '../screens/main/WalletScreen';
import { colors } from '../theme';
import type {
  AuthStackParamList,
  MainTabParamList,
  RootStackParamList,
} from './types';

const RootStack = createNativeStackNavigator<RootStackParamList>();
const AuthStack = createNativeStackNavigator<AuthStackParamList>();
const Tab = createBottomTabNavigator<MainTabParamList>();

const headerOptions = {
  headerStyle: {
    backgroundColor: colors.navy,
  },
  headerTintColor: colors.white,
  headerTitleStyle: {
    color: colors.white,
    fontWeight: '700' as const,
  },
  headerBackTitle: 'Back',
};

function AuthNavigator() {
  return (
    <AuthStack.Navigator
      screenOptions={{
        ...headerOptions,
        headerShown: false,
      }}>
      <AuthStack.Screen name="Login" component={LoginScreen} />
      <AuthStack.Screen name="Register" component={RegisterScreen} />
    </AuthStack.Navigator>
  );
}

function MainNavigator() {
  return (
    <Tab.Navigator
      screenOptions={{
        ...headerOptions,
        tabBarActiveTintColor: colors.orange,
        tabBarInactiveTintColor: colors.muted,
        tabBarStyle: {
          backgroundColor: colors.surface,
          borderTopColor: colors.border,
        },
        headerShown: true,
      }}>
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        options={{ title: 'Dashboard' }}
      />
      <Tab.Screen
        name="Tasks"
        component={TasksScreen}
        options={{ title: 'Tasks' }}
      />
      <Tab.Screen
        name="Campaigns"
        component={CampaignsScreen}
        options={{ title: 'Campaigns' }}
      />
      <Tab.Screen
        name="Wallet"
        component={WalletScreen}
        options={{ title: 'Wallet' }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileScreen}
        options={{ title: 'Profile' }}
      />
    </Tab.Navigator>
  );
}

function Splash() {
  return (
    <View style={styles.splash}>
      <Logo size="large" />
      <ActivityIndicator color={colors.orange} size="small" />
    </View>
  );
}

export function AppNavigator() {
  const { isLoading, isAuthenticated } = useAuth();

  if (isLoading) {
    return <Splash />;
  }

  return (
    <RootStack.Navigator screenOptions={headerOptions}>
      {isAuthenticated ? (
        <>
          <RootStack.Screen
            name="Main"
            component={MainNavigator}
            options={{ headerShown: false }}
          />
          <RootStack.Screen
            name="Earnings"
            component={EarningsScreen}
            options={{ title: 'Earnings history' }}
          />
          <RootStack.Screen
            name="Notifications"
            component={NotificationsScreen}
          />
          <RootStack.Screen
            name="Announcements"
            component={AnnouncementsScreen}
          />
          <RootStack.Screen
            name="PayoutAccounts"
            component={PayoutAccountsScreen}
            options={{ title: 'Payout accounts' }}
          />
          <RootStack.Screen name="Withdraw" component={WithdrawScreen} />
          <RootStack.Screen name="Deposit" component={DepositScreen} />
          <RootStack.Screen
            name="EditProfile"
            component={EditProfileScreen}
            options={{ title: 'Edit profile' }}
          />
          <RootStack.Screen
            name="TaskRun"
            component={TaskRunScreen}
            options={{ title: 'Watch task' }}
          />
        </>
      ) : (
        <RootStack.Screen
          name="Auth"
          component={AuthNavigator}
          options={{ headerShown: false }}
        />
      )}
    </RootStack.Navigator>
  );
}

const styles = StyleSheet.create({
  splash: {
    flex: 1,
    backgroundColor: colors.navy,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
