import { StyleSheet, Text, View } from 'react-native';
import { colors, radius } from '../theme';

type StatusTone = 'neutral' | 'success' | 'warning' | 'danger';

type StatusPillProps = {
  label: string;
  tone?: StatusTone;
};

function toneFor(label: string): StatusTone {
  const value = label.toLowerCase();

  if (['complete', 'approved', 'active', 'paid'].some(key => value.includes(key))) {
    return 'success';
  }

  if (['pending', 'progress', 'review', 'started'].some(key => value.includes(key))) {
    return 'warning';
  }

  if (['reject', 'failed', 'expired', 'cancel'].some(key => value.includes(key))) {
    return 'danger';
  }

  return 'neutral';
}

export function StatusPill({ label, tone }: StatusPillProps) {
  const resolvedTone = tone ?? toneFor(label);

  return (
    <View style={[styles.pill, pillStyles[resolvedTone]]}>
      <Text style={[styles.label, labelStyles[resolvedTone]]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  pill: {
    borderRadius: radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  label: {
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'capitalize',
  },
  neutral: {
    backgroundColor: '#e8edf4',
  },
  neutralLabel: {
    color: colors.navySoft,
  },
  success: {
    backgroundColor: '#dcfce7',
  },
  successLabel: {
    color: '#047857',
  },
  warning: {
    backgroundColor: '#ffedd5',
  },
  warningLabel: {
    color: '#c2410c',
  },
  danger: {
    backgroundColor: '#fee2e2',
  },
  dangerLabel: {
    color: '#b91c1c',
  },
});

const pillStyles = {
  neutral: styles.neutral,
  success: styles.success,
  warning: styles.warning,
  danger: styles.danger,
};

const labelStyles = {
  neutral: styles.neutralLabel,
  success: styles.successLabel,
  warning: styles.warningLabel,
  danger: styles.dangerLabel,
};
