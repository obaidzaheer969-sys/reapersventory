import { StyleSheet, type ViewStyle } from 'react-native';

/**
 * Builds a percentage width style without putting an object literal in a
 * style prop, so dynamic progress bars stay lint-clean.
 */
export function percentWidthStyle(percent: number): ViewStyle {
  const safePercent = Math.min(100, Math.max(0, Number.isFinite(percent) ? percent : 0));

  return StyleSheet.create({
    width: { width: `${safePercent}%` },
  }).width;
}
