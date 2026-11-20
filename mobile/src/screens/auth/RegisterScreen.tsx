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

export function RegisterScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<AuthStackParamList>>();
  const { register } = useAuth();
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [terms, setTerms] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async () => {
    if (
      firstName.trim() === '' ||
      lastName.trim() === '' ||
      email.trim() === '' ||
      password === ''
    ) {
      setError('Complete all required fields.');

      return;
    }

    if (password !== confirmation) {
      setError('Passwords do not match.');

      return;
    }

    if (!terms) {
      setError('Please agree to the terms to continue.');

      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      await register({
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        password,
        password_confirmation: confirmation,
        terms,
      });
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
          <Text style={styles.title}>Create your account</Text>
          <Text style={styles.subtitle}>Start earning with simple tasks.</Text>

          <View style={styles.form}>
            <Field
              label="First name"
              value={firstName}
              onChangeText={setFirstName}
              placeholder="First name"
              autoCapitalize="words"
            />
            <Field
              label="Last name"
              value={lastName}
              onChangeText={setLastName}
              placeholder="Last name"
              autoCapitalize="words"
            />
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
              placeholder="At least 8 characters"
              secureTextEntry
            />
            <Field
              label="Confirm password"
              value={confirmation}
              onChangeText={setConfirmation}
              placeholder="Repeat your password"
              secureTextEntry
            />

            <TouchableOpacity
              accessibilityRole="checkbox"
              accessibilityState={{ checked: terms }}
              onPress={() => setTerms(current => !current)}
              style={styles.termsRow}>
              <View style={[styles.checkbox, terms && styles.checkboxActive]}>
                {terms ? <Text style={styles.checkmark}>✓</Text> : null}
              </View>
              <Text style={styles.termsText}>
                I agree to the terms and conditions
              </Text>
            </TouchableOpacity>

            {error ? <Text style={styles.error}>{error}</Text> : null}

            <PrimaryButton
              title="Create account"
              onPress={() => handleSubmit()}
              loading={submitting}
            />

            <TouchableOpacity
              onPress={() => navigation.navigate('Login')}
              style={styles.linkRow}>
              <Text style={styles.linkText}>
                Already have an account? Sign in
              </Text>
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
    fontSize: 24,
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
  termsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: spacing.md,
  },
  checkbox: {
    width: 20,
    height: 20,
    borderRadius: 5,
    borderWidth: 1,
    borderColor: colors.muted,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: spacing.sm,
  },
  checkboxActive: {
    backgroundColor: colors.orange,
    borderColor: colors.orange,
  },
  checkmark: {
    color: colors.white,
    fontSize: 13,
    fontWeight: '800',
  },
  termsText: {
    color: colors.text,
    fontSize: 13,
    flexShrink: 1,
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
