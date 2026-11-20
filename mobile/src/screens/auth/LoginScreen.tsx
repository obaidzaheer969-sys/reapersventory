import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { getErrorMessage } from '../../api/client';
import { Field } from '../../components/Field';
import { Logo } from '../../components/Logo';
import { PrimaryButton } from '../../components/PrimaryButton';
import { useAuth } from '../../context/AuthContext';
import type { AuthStackParamList } from '../../navigation/types';
import { colors, spacing } from '../../theme';

export function LoginScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<AuthStackParamList>>();
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async () => {
    if (email.trim() === '' || password === '') {
      setError('Enter your email and password.');

      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      await login(email.trim(), password);
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.root}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.flex}>
        <ScrollView
          contentContainerStyle={styles.container}
          keyboardShouldPersistTaps="handled">
          <Logo size="large" />
          <Text style={styles.title}>Welcome back</Text>
          <Text style={styles.subtitle}>Sign in to continue earning.</Text>

          <View style={styles.form}>
            <Field
              label="Email address"
              value={email}
              onChangeText={setEmail}
              placeholder="you@example.com"
              keyboardType="email-address"
            />
            <Field
              label="Password"
              value={password}
              onChangeText={setPassword}
              placeholder="Enter your password"
              secureTextEntry
            />

            {error ? <Text style={styles.error}>{error}</Text> : null}

            <PrimaryButton
              title="Sign in"
              onPress={() => handleSubmit()}
              loading={submitting}
            />

            <TouchableOpacity
              onPress={() => navigation.navigate('Register')}
              style={styles.linkRow}>
              <Text style={styles.linkText}>New here? Create an account</Text>
            </TouchableOpacity>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: colors.navy,
  },
  flex: {
    flex: 1,
  },
  container: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
    paddingBottom: 40,
  },
  title: {
    color: colors.white,
    fontSize: 26,
    fontWeight: '800',
    textAlign: 'center',
    marginTop: spacing.lg,
  },
  subtitle: {
    color: '#a9b6c9',
    fontSize: 14,
    textAlign: 'center',
    marginTop: 6,
    marginBottom: spacing.xl,
  },
  form: {
    backgroundColor: colors.surface,
    borderRadius: 20,
    padding: spacing.lg,
  },
  error: {
    color: colors.danger,
    fontSize: 13,
    marginBottom: spacing.md,
  },
  linkRow: {
    alignItems: 'center',
    marginTop: spacing.lg,
    paddingVertical: spacing.sm,
  },
  linkText: {
    color: colors.orange,
    fontSize: 14,
    fontWeight: '700',
  },
});
