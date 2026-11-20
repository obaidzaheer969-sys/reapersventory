import {
  ActivityIndicator,
  StyleSheet,
  Text,
  TouchableOpacity,
  type StyleProp,
  type TextStyle,
  type ViewStyle,
} from 'react-native';
import { colors, radius, spacing } from '../theme';

type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'danger';

type PrimaryButtonProps = {
  title: string;
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
  variant?: ButtonVariant;
};

export function PrimaryButton({
  title,
  onPress,
  loading = false,
  disabled = false,
  variant = 'primary',
}: PrimaryButtonProps) {
  const isDisabled = disabled || loading;

  return (
    <TouchableOpacity
      accessibilityRole="button"
      accessibilityLabel={title}
      disabled={isDisabled}
      onPress={onPress}
      style={[
        styles.button,
        buttonVariants[variant],
        isDisabled && styles.disabled,
      ]}>
      {loading ? (
        <ActivityIndicator
          color={variant === 'primary' || variant === 'danger' ? colors.white : colors.orange}
          size="small"
        />
      ) : (
        <Text style={[styles.label, labelVariants[variant]]}>{title}</Text>
      )}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  button: {
    minHeight: 48,
    borderRadius: radius.md,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.lg,
  },
  label: {
    fontSize: 15,
    fontWeight: '700',
  },
  primary: {
    backgroundColor: colors.orange,
  },
  primaryLabel: {
    color: colors.white,
  },
  secondary: {
    backgroundColor: colors.mint,
  },
  secondaryLabel: {
    color: colors.white,
  },
  outline: {
    backgroundColor: 'transparent',
    borderWidth: 1,
    borderColor: colors.orange,
  },
  outlineLabel: {
    color: colors.orange,
  },
  danger: {
    backgroundColor: colors.danger,
  },
  dangerLabel: {
    color: colors.white,
  },
  disabled: {
    opacity: 0.55,
  },
});

const buttonVariants: Record<ButtonVariant, StyleProp<ViewStyle>> = {
  primary: styles.primary,
  secondary: styles.secondary,
  outline: styles.outline,
  danger: styles.danger,
};

const labelVariants: Record<ButtonVariant, StyleProp<TextStyle>> = {
  primary: styles.primaryLabel,
  secondary: styles.secondaryLabel,
  outline: styles.outlineLabel,
  danger: styles.dangerLabel,
};
