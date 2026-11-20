import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useState } from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Logo } from '../../components/Logo';
import { PrimaryButton } from '../../components/PrimaryButton';
import { Screen } from '../../components/Screen';
import { useAuth } from '../../context/AuthContext';
import type { RootStackParamList } from '../../navigation/types';
import { colors, radius } from '../../theme';
import { formatMoney } from '../../utils/format';

export function ProfileScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const { user, logout } = useAuth();
  const [loggingOut, setLoggingOut] = useState(false);

  const handleLogout = async () => {
    setLoggingOut(true);

    try {
      await logout();
    } finally {
      setLoggingOut(false);
    }
  };

  return (
    <Screen>
      <View style={styles.header}>
        <Logo size="small" />
        <Text style={styles.name}>{user?.name || 'Earner'}</Text>
        <Text style={styles.email}>{user?.email || ''}</Text>
        <View style={styles.badges}>
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{user?.role || 'earner'}</Text>
          </View>
          <View style={styles.badge}>
            <Text style={styles.badgeText}>
              {user?.is_active ? 'Active' : 'Inactive'}
            </Text>
          </View>
        </View>
        <Text style={styles.balance}>
          Balance {formatMoney(user?.wallet_balance ?? 0)}
        </Text>
      </View>

      <View>
        <View style={styles.menu}>
          <MenuRow label="Edit profile" onPress={() => navigation.navigate('EditProfile')} />
          <MenuRow label="Earnings history" onPress={() => navigation.navigate('Earnings')} />
          <MenuRow label="Payout accounts" onPress={() => navigation.navigate('PayoutAccounts')} />
          <MenuRow label="Notifications" onPress={() => navigation.navigate('Notifications')} />
          <MenuRow label="Announcements" onPress={() => navigation.navigate('Announcements')} />
          <MenuRow label="Deposit" onPress={() => navigation.navigate('Deposit')} />
          <MenuRow label="Withdraw" onPress={() => navigation.navigate('Withdraw')} />
        </View>

        <View style={styles.logout}>
          <PrimaryButton
            title="Sign out"
            variant="danger"
            onPress={() => handleLogout()}
            loading={loggingOut}
          />
        </View>
      </View>
    </Screen>
  );
}

function MenuRow({
  label,
  onPress,
}: {
  label: string;
  onPress: () => void;
}) {
  return (
    <TouchableOpacity onPress={onPress} style={styles.menuRow}>
      <Text style={styles.menuText}>{label}</Text>
      <Text style={styles.chevron}>›</Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  header: {
    alignItems: 'center',
    backgroundColor: colors.navy,
    borderRadius: radius.lg,
    padding: 20,
    marginBottom: 12,
  },
  name: {
    color: colors.white,
    fontSize: 19,
    fontWeight: '800',
    marginTop: 8,
  },
  email: {
    color: '#a9b6c9',
    fontSize: 13,
    marginTop: 3,
  },
  badges: {
    flexDirection: 'row',
    marginTop: 10,
  },
  badge: {
    backgroundColor: colors.navyLight,
    borderRadius: radius.pill,
    paddingHorizontal: 12,
    paddingVertical: 4,
    marginHorizontal: 4,
  },
  badgeText: {
    color: colors.white,
    fontSize: 11,
    textTransform: 'capitalize',
  },
  balance: {
    color: colors.mint,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 12,
  },
  menu: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
  },
  menuRow: {
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  menuText: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  chevron: {
    color: colors.muted,
    fontSize: 22,
  },
  logout: {
    marginTop: 18,
  },
});
