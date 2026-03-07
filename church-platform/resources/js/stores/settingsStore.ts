import { create } from 'zustand';

interface AppSettings {
  app_name: string;
  app_tagline: string;
  logo_light: string | null;
  logo_dark: string | null;
  favicon: string | null;
  registration_open: boolean;
  email_confirmation_required: boolean;
  daily_verse_enabled: boolean;
  greeting_enabled: boolean;
}

interface SettingsState {
  settings: AppSettings | null;
  loaded: boolean;
  setSettings: (s: AppSettings) => void;
}

const defaults: AppSettings = {
  app_name: 'Church Platform',
  app_tagline: '',
  logo_light: null,
  logo_dark: null,
  favicon: null,
  registration_open: true,
  email_confirmation_required: false,
  daily_verse_enabled: true,
  greeting_enabled: true,
};

export const useSettingsStore = create<SettingsState>()((set) => ({
  settings: null,
  loaded: false,
  setSettings: (settings) => set({ settings, loaded: true }),
}));

export const getSettings = (): AppSettings => {
  return useSettingsStore.getState().settings ?? defaults;
};
