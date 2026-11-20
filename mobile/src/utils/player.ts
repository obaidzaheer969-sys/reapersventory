export type PlayerEvent = 'playing' | 'paused';

export function stripEmbedOrigin(url: string | null): string | null {
  if (!url) {
    return null;
  }

  try {
    const parsed = new URL(url);

    parsed.searchParams.delete('origin');

    return parsed.toString();
  } catch {
    return url;
  }
}

export function parsePlayerMessage(raw: string): PlayerEvent | null {
  try {
    const data = JSON.parse(raw) as {
      type?: unknown;
      event?: unknown;
      info?: unknown;
    };

    if (data.type === 'playing' || data.type === 'paused') {
      return data.type;
    }

    if (data.event === 'infoDelivery' && data.info !== null && typeof data.info === 'object') {
      const playerState = (data.info as { playerState?: unknown }).playerState;

      if (playerState === 1) {
        return 'playing';
      }

      if (playerState === 0 || playerState === 2) {
        return 'paused';
      }

      return null;
    }

    if (data.event !== 'onStateChange') {
      return null;
    }

    if (data.info === 1) {
      return 'playing';
    }

    if (data.info === 0 || data.info === 2) {
      return 'paused';
    }

    return null;
  } catch {
    return null;
  }
}

export const PLAYER_BRIDGE_SCRIPT = `
(function () {
  function send(type) {
    if (!window.ReactNativeWebView || typeof window.ReactNativeWebView.postMessage !== 'function') {
      return;
    }

    window.ReactNativeWebView.postMessage(JSON.stringify({ type: type }));
  }

  window.addEventListener('message', function (event) {
    try {
      var data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;

      if (!data) {
        return;
      }

      if (data.event === 'infoDelivery' && data.info && typeof data.info.playerState === 'number') {
        if (data.info.playerState === 1) {
          send('playing');
        } else if (data.info.playerState === 0 || data.info.playerState === 2) {
          send('paused');
        }
        return;
      }

      if (data.event !== 'onStateChange') {
        return;
      }

      if (data.info === 1) {
        send('playing');
      } else if (data.info === 0 || data.info === 2) {
        send('paused');
      }
    } catch (error) {}
  });

  var facebookAttempts = 0;
  var facebookTimer = window.setInterval(function () {
    facebookAttempts += 1;

    if (window.FB && window.FB.Event && window.FB.Event.subscribe) {
      window.FB.Event.subscribe('xfbml.ready', function (message) {
        if (!message || message.type !== 'video' || !message.instance) {
          return;
        }

        var player = message.instance;

        player.subscribe('startedPlaying', function () { send('playing'); });
        player.subscribe('paused', function () { send('paused'); });
        player.subscribe('finishedPlaying', function () { send('paused'); });
      });

      window.clearInterval(facebookTimer);
      return;
    }

    if (facebookAttempts >= 30) {
      window.clearInterval(facebookTimer);
    }
  }, 500);

  true;
})();
`;
