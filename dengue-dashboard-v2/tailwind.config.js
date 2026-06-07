/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        // Medical enterprise red — softer than pure #DC2626
        primary: {
          50:  '#FEF2F2',
          100: '#FEE2E2',
          200: '#FECACA',
          300: '#FCA5A5',
          400: '#F87171',
          500: '#EF4444',
          600: '#DC2626',
          700: '#B91C1C',
          800: '#991B1B',
          900: '#7F1D1D',
        },
        surface: {
          DEFAULT: '#FFFFFF',
          muted:   '#F8FAFC',
          subtle:  '#F1F5F9',
        },
        ink: {
          DEFAULT: '#0F172A',
          muted:   '#334155',
          subtle:  '#64748B',
          faint:   '#94A3B8',
        },
        line: {
          DEFAULT: '#E2E8F0',
          strong:  '#CBD5E1',
          subtle:  '#F1F5F9',
        },
        positive: '#DC2626',
        negative: '#10B981',
        warning:  '#F59E0B',
      },
      // Single font family for everything - enterprise consistency
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
      fontSize: {
        '2xs': ['0.6875rem', { lineHeight: '1rem', letterSpacing: '0.04em' }],
      },
      // Display weights & tracking for enterprise feel
      letterSpacing: {
        'tightest': '-0.04em',
        'tighter':  '-0.025em',
      },
      boxShadow: {
        'card':       '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.04)',
        'card-hover': '0 4px 8px -2px rgb(15 23 42 / 0.06), 0 2px 4px -2px rgb(15 23 42 / 0.04)',
      },
      animation: {
        'pulse-soft': 'pulse-soft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'scan':       'scan 2s linear infinite',
      },
      keyframes: {
        'pulse-soft': {
          '0%, 100%': { opacity: '1' },
          '50%':      { opacity: '0.55' },
        },
        'scan': {
          '0%':   { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(100%)' },
        },
      },
    },
  },
  plugins: [],
}
