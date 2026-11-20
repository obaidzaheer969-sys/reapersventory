import { countdownFromDeadline, deadlineFromRemaining } from '../src/utils/timer';

test('keeps a visible countdown ticking from the server remaining time', () => {
  const deadline = deadlineFromRemaining(20, 1000);

  expect(countdownFromDeadline(deadline, 1000)).toBe(20);
  expect(countdownFromDeadline(deadline, 6000)).toBe(15);
  expect(countdownFromDeadline(deadline, 21000)).toBe(0);
});

test('does not tick when the server timer is paused or finished', () => {
  expect(deadlineFromRemaining(0, 1000)).toBeNull();
  expect(countdownFromDeadline(null, 1000)).toBe(0);
});
