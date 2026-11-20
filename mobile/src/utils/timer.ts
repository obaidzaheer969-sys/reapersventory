export function deadlineFromRemaining(
  remainingSeconds: number,
  nowMs: number = Date.now(),
): number | null {
  if (!Number.isFinite(remainingSeconds) || remainingSeconds <= 0) {
    return null;
  }

  return nowMs + Math.ceil(remainingSeconds) * 1000;
}

export function countdownFromDeadline(
  deadlineMs: number | null,
  nowMs: number = Date.now(),
): number {
  if (deadlineMs === null || !Number.isFinite(deadlineMs)) {
    return 0;
  }

  return Math.max(0, Math.ceil((deadlineMs - nowMs) / 1000));
}
