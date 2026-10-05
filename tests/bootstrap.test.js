import { expect, it } from 'vitest';

it('configures axios for XMLHttpRequest requests', async () => {
    globalThis.window = {};
    const { default: axios } = await import('axios');
    await import('../resources/js/bootstrap.js');

    expect(globalThis.window.axios).toBe(axios);
    expect(globalThis.window.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest');
});
