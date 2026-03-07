import { create } from 'zustand';
import { persist } from 'zustand/middleware';

type ColorMode = 'light' | 'dark' | 'system';

interface ThemeState {
  colorMode: ColorMode;
  cssVars: string;
  setColorMode: (mode: ColorMode) => void;
  setCssVars: (css: string) => void;
  applyTheme: () => void;
}

export const useThemeStore = create<ThemeState>()(
  persist(
    (set, get) => ({
      colorMode: 'system',
      cssVars: '',

      setColorMode: (colorMode) => {
        set({ colorMode });
        get().applyTheme();
      },

      setCssVars: (cssVars) => {
        set({ cssVars });
        // Inject CSS vars into document
        let el = document.getElementById('theme-vars');
        if (!el) {
          el = document.createElement('style');
          el.id = 'theme-vars';
          document.head.appendChild(el);
        }
        el.textContent = cssVars;
      },

      applyTheme: () => {
        const { colorMode } = get();
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = colorMode === 'dark' || (colorMode === 'system' && prefersDark);
        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
      },
    }),
    {
      name: 'church-theme',
      partialize: (state) => ({ colorMode: state.colorMode }),
      onRehydrateStorage: () => (state) => {
        state?.applyTheme();
      },
    }
  )
);
