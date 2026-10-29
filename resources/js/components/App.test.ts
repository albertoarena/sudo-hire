import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import App from './App.vue';

describe('App', () => {
    it('shows the app name', () => {
        expect(mount(App).text()).toContain('Sudo Hire');
    });
});
