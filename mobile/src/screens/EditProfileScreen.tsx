import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { getErrorMessage } from '../api/client';
import { endpoints } from '../api/endpoints';
import { Card } from '../components/Card';
import { Field } from '../components/Field';
import { PrimaryButton } from '../components/PrimaryButton';
import { Screen } from '../components/Screen';
import { useAuth } from '../context/AuthContext';
import type { RootStackParamList } from '../navigation/types';
import { colors } from '../theme';

export function EditProfileScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const { user, refreshUser } = useAuth();
  const [name, setName] = useState(user?.name ?? '');
  const [email, setEmail] = useState(user?.email ?? '');
  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (user) {
      setName(user.name);
      setEmail(user.email);
    }
  }, [user]);

  const handleSave = async () => {
    setError(null);

    if (name.trim() === '' || email.trim() === '') {
      setError('Name and email are required.');

      return;
    }

    if (password !== confirmation) {
      setError('New passwords do not match.');

      return;
    }

    if (password !== '' && currentPassword === '') {
      setError('Enter your current password to change it.');

      return;
    }

    setSaving(true);

    try {
      await endpoints.updateProfile({
        name: name.trim(),
        email: email.trim(),
        current_password: currentPassword || undefined,
        password: password || undefined,
        password_confirmation: confirmation || undefined,
      });

      await refreshUser();
      navigation.goBack();
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen>
      <Card>
        <Text style={styles.title}>Personal details</Text>
        <Field
          label="Full name"
          value={name}
          onChangeText={setName}
          placeholder="Your name"
          autoCapitalize="words"
        />
        <Field
          label="Email address"
          value={email}
          onChangeText={setEmail}
          placeholder="you@example.com"
          keyboardType="email-address"
        />

        <View style={styles.divider} />
        <Text style={styles.title}>Change password</Text>
        <Text style={styles.subtitle}>
          Leave the password fields empty to keep your current password.
        </Text>
        <Field
          label="Current password"
          value={currentPassword}
          onChangeText={setCurrentPassword}
          placeholder="Current password"
          secureTextEntry
        />
        <Field
          label="New password"
          value={password}
          onChangeText={setPassword}
          placeholder="New password"
          secureTextEntry
        />
        <Field
          label="Confirm new password"
          value={confirmation}
          onChangeText={setConfirmation}
          placeholder="Repeat new password"
          secureTextEntry
        />

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <PrimaryButton
          title="Save changes"
          onPress={() => handleSave()}
          loading={saving}
        />
      </Card>
      <View style={styles.spacer} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  title: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 12,
  },
  subtitle: {
    color: colors.muted,
    fontSize: 12,
    marginBottom: 12,
  },
  divider: {
    height: 1,
    backgroundColor: colors.border,
    marginVertical: 12,
  },
  error: {
    color: colors.danger,
    fontSize: 13,
    marginBottom: 10,
  },
  spacer: {
    height: 24,
  },
});
