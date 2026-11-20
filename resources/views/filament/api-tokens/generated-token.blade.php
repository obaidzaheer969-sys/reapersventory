@php
    $token = (string) ($token ?? '');
    $name = (string) ($name ?? '');
@endphp

<div x-data="{ token: {{ \Illuminate\Support\Js::from($token) }}, copied: false }" style="display: grid; gap: .75rem;">
    <p style="margin: 0; font-size: .875rem; line-height: 1.5; color: var(--gray-600, #4b5563);">
        Copy this token now and add it to the app. For security, it cannot be shown again.
    </p>

    @if ($name !== '')
        <p style="margin: 0; font-size: .8125rem; color: var(--gray-500, #6b7280);">
            Token name: <strong style="color: var(--gray-950, #030712);">{{ $name }}</strong>
        </p>
    @endif

    <code
        x-text="token"
        style="display: block; padding: .75rem; border: 1px solid var(--gray-200, #e5e7eb); border-radius: .5rem; background: var(--gray-50, #f9fafb); color: var(--gray-950, #030712); font-size: .8125rem; line-height: 1.5; word-break: break-all; user-select: all;"
    ></code>

    <button
        type="button"
        @click="navigator.clipboard.writeText(token).then(() => copied = true).catch(() => copied = false)"
        style="justify-self: start; padding: .5rem .875rem; border: 0; border-radius: .5rem; background: var(--primary-500, #f15a24); color: #fff; font-size: .8125rem; font-weight: 600; cursor: pointer;"
    >
        <span x-show="! copied">Copy token</span>
        <span x-show="copied">Copied</span>
    </button>
</div>
