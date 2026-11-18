@if ($adsEnabled && $adAutoCode !== '')
    <script data-earn-ad-click-gate>
        (() => {
            const storageKey = 'earn-to-pkr-ad-clicked-links';

            const readSeenLinks = () => {
                try {
                    const stored = JSON.parse(sessionStorage.getItem(storageKey) ?? '{}');

                    return stored && typeof stored === 'object' && ! Array.isArray(stored) ? stored : {};
                } catch {
                    return {};
                }
            };

            const seenLinks = readSeenLinks();

            const eligibleLinkKey = (event) => {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return null;
                }

                if (! (event.target instanceof Element)) {
                    return null;
                }

                const link = event.target.closest('a[href]');

                if (! link || link.hasAttribute('download') || link.hasAttribute('data-no-ad')) {
                    return null;
                }

                const target = (link.getAttribute('target') ?? '').toLowerCase();

                if (target === '_blank' || target === '_new') {
                    return null;
                }

                if (document.querySelector('[data-lock-back], [data-lock-inline-back]') !== null) {
                    return null;
                }

                const url = new URL(link.href, window.location.href);

                if (url.origin !== window.location.origin || ! ['http:', 'https:'].includes(url.protocol)) {
                    return null;
                }

                if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash !== '') {
                    return null;
                }

                return url.pathname + url.search;
            };

            window.addEventListener('click', (event) => {
                const key = eligibleLinkKey(event);

                if (key === null) {
                    return;
                }

                if (seenLinks[key]) {
                    event.stopImmediatePropagation();

                    return;
                }

                seenLinks[key] = true;

                try {
                    sessionStorage.setItem(storageKey, JSON.stringify(seenLinks));
                } catch {
                    // The in-memory gate still works for the current page.
                }
            }, true);
        })();
    </script>
@endif

@if ($siteVerificationTag !== '')
    {!! $siteVerificationTag !!}
@endif

@if ($adAutoCode !== '')
    {!! $adAutoCode !!}
@endif
