import {
  parsePlayerMessage,
  PLAYER_BRIDGE_SCRIPT,
  stripEmbedOrigin,
} from '../src/utils/player';

test('parses YouTube playback state messages', () => {
  expect(
    parsePlayerMessage(JSON.stringify({ event: 'onStateChange', info: 1 })),
  ).toBe('playing');
  expect(
    parsePlayerMessage(JSON.stringify({ event: 'onStateChange', info: 2 })),
  ).toBe('paused');
  expect(
    parsePlayerMessage(JSON.stringify({ event: 'onStateChange', info: 0 })),
  ).toBe('paused');
  expect(
    parsePlayerMessage(
      JSON.stringify({ event: 'infoDelivery', info: { playerState: 1 } }),
    ),
  ).toBe('playing');
});

test('parses bridge messages and ignores unknown payloads', () => {
  expect(parsePlayerMessage(JSON.stringify({ type: 'playing' }))).toBe('playing');
  expect(parsePlayerMessage(JSON.stringify({ type: 'paused' }))).toBe('paused');
  expect(parsePlayerMessage('not json')).toBeNull();
  expect(
    parsePlayerMessage(JSON.stringify({ event: 'onStateChange', info: -1 })),
  ).toBeNull();
});

test('removes the web origin so YouTube sends events inside the mobile WebView', () => {
  expect(
    stripEmbedOrigin(
      'https://www.youtube.com/embed/abc123?enablejsapi=1&origin=https%3A%2F%2Freapersventory.com',
    ),
  ).toBe('https://www.youtube.com/embed/abc123?enablejsapi=1');
  expect(stripEmbedOrigin(null)).toBeNull();
});

test('player bridge observes playback and forwards it to React Native', () => {
  expect(PLAYER_BRIDGE_SCRIPT).toContain('onStateChange');
  expect(PLAYER_BRIDGE_SCRIPT).toContain('ReactNativeWebView.postMessage');
  expect(PLAYER_BRIDGE_SCRIPT).toContain('startedPlaying');
});
