import { inject, type InjectionKey, reactive, ref, type Ref } from 'vue';
import { api, ApiError, ServerStopped } from '../shared/api';
import type { Country, Source } from '../shared/types';
import type { Settings } from './types';

export type SettingKey = keyof Settings;

/**
 * The state that every pane shares. A control saves through PUT /api/settings when it changes.
 * A refused value (422) keeps the stored settings and puts the API message under the field.
 */
export interface SettingsStore {
    settings: Ref<Settings | null>;
    countries: Ref<Country[]>;
    sources: Ref<Source[]>;
    errors: Partial<Record<string, string>>;
    stopped: Ref<boolean>;
    save: (field: SettingKey, value: Settings[SettingKey]) => Promise<boolean>;
    run: (work: () => Promise<void>) => Promise<void>;
    loadSources: () => Promise<void>;
}

export const STORE: InjectionKey<SettingsStore> = Symbol('settings');

export function createStore(): SettingsStore {
    const settings = ref<Settings | null>(null);
    const countries = ref<Country[]>([]);
    const sources = ref<Source[]>([]);
    const errors = reactive<Partial<Record<string, string>>>({});
    const stopped = ref(false);

    async function run(work: () => Promise<void>): Promise<void> {
        try {
            await work();
            stopped.value = false;
        } catch (error) {
            if (error instanceof ServerStopped) {
                stopped.value = true;
            } else {
                console.error(error);
            }
        }
    }

    async function save(field: SettingKey, value: Settings[SettingKey]): Promise<boolean> {
        let saved = false;

        await run(async () => {
            try {
                settings.value = await api.saveSettings({ [field]: value });
                delete errors[field];
                saved = true;
            } catch (error) {
                if (!(error instanceof ApiError && error.status === 422)) {
                    throw error;
                }

                errors[field] = error.message;
            }
        });

        return saved;
    }

    const loadSources = () =>
        run(async () => {
            sources.value = await api.sources();
        });

    return { settings, countries, sources, errors, stopped, save, run, loadSources };
}

export function useStore(): SettingsStore {
    const store = inject(STORE);

    if (!store) {
        throw new Error('The settings store is not provided.');
    }

    return store;
}
